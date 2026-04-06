# Задание

Спроектировать механизм конкурентного резервирования объёма перевозки в системе торгов.

---

## 📄 Механизм конкурентного резервирования объёма перевозки

### 1. Общее описание

В системе торгов существует лот на перевозку фиксированного объёма груза (например, 1 000 тонн семян подсолнечника).

В торгах одновременно участвуют множество перевозчиков, каждый из которых может зарезервировать объем на перевозку рамках одного лота.

Запросы на резервирование поступают параллельно и могут обрабатываться несколькими инстансами сервиса (горизонтальное масштабирование).

### 2. Требования к механизму резервирования

Механизм должен:

- гарантировать соблюдение инварианта:
  - суммарный зарезервированный объём ≤ общий объём лота (1 000 тонн)
- корректно обрабатывать конкурентные запросы на резервирование остатка
- не использовать глобальные блокировки на уровне приложения (mutex, semaphore)
- масштабироваться при увеличении количества воркеров и инстансов
- допускать отказ и повтор попытки (retry) при конфликте
- обеспечивать высокую производительность при высокой нагрузке

### 3. Жизненный цикл лота

Лот имеет состояние доступности для резервирования:

**Возможные статусы:**

- `CREATED` — лот создан, но ещё не доступен для резервирования
- `OPEN` — лот открыт для резервирования
- `CLOSED` — лот закрыт, резервирование невозможно

**Переходы состояний:**

```
CREATED → OPEN
OPEN → CLOSED
```

**Условия закрытия лота:**

Лот переходит в состояние `CLOSED` при наступлении одного из условий:

- **Истечение времени действия лота (TTL)**
  - наступает заданное время окончания торгов по лоту
- **Принудительное закрытие**
  - лот закрывается вручную или внешней системой

### 4. Ограничения (инварианты)

В системе должны соблюдаться следующие ограничения:

- суммарный объём резервов по лоту не превышает 1 000 тонн (может настраиваться для каждого лота отдельно)
- один перевозчик может зарезервировать весь объем по лоту (1000 тонн), если уверен что сможет выполнить заказ.
- резервирование возможно только для лотов в статусе `OPEN`

### 5. Доменные события

Ниже приведён полный перечень доменных событий, используемых в системе.

#### 📦 События жизненного цикла лота

- `LotCreated` — создан новый лот
- `LotOpened` — лот открыт для резервирования
- `LotClosed` — лот закрыт (финальное состояние)
- `LotFullyReserved` — весь объём лота зарезервирован
- `LotExpired` — лот закрыт по истечению времени

#### 📥 События резервирования

- `ReservationSucceeded` — резерв успешно создан
- `ReservationFailed` — резервирование не удалось (техническая ошибка или конфликт)

#### ⚙️ События конкурентного взаимодействия

- `CapacityCheckPassed` — проверка доступного объёма прошла успешно

#### 🔄 Дополнительные события (опционально)

- `ReservationCancelled` — резерв отменён

### 6. Общий поток обработки запроса

1. Поступает событие `ReservationRequested`
2. Выполняются проверки:
   - лот существует
   - лот находится в статусе `OPEN`
   - объём ≤ 1000 тонн
3. Выполняется попытка атомарного резервирования
4. В зависимости от результата:
   - успех → `ReservationSucceeded`
   - отказ по бизнес-ограничениям → `ReservationRejected`
   - конфликт → `ReservationConflictDetected` + возможный retry
5. При достижении полного объёма:
   - `LotFullyReserved`
   - `LotClosed`

### 7. Особенности конкурентной обработки

- параллельные запросы обрабатываются без глобальных блокировок
- используется атомарное обновление состояния лота на уровне базы данных
- при конфликте допускается повторная попытка выполнения операции
- корректность обеспечивается за счёт проверки инвариантов при записи

---

## 📦 Доменные сущности системы торгов перевозок

В системе реализован механизм конкурентного распределения объёма перевозки между подрядчиками в рамках торгов (аукциона).

**Основная цель модели** — обеспечить:

- корректное распределение ограниченного объема лота
- поддержку конкурентных ставок
- перераспределение объёма в пользу более выгодных (дешевых) предложений
- соблюдение инварианта:
  - суммарный `allocated_volume` ≤ `total_volume`

### 🧩 Основные доменные сущности

Система построена вокруг следующих ключевых сущностей:

#### 1️⃣ Contractor (Подрядчик)

Представляет участника торгов — перевозчика, который подаёт ставки на выполнение перевозки.

Подрядчик:

- идентифицируется уникальным ID
- связан с договором (agreement)
- может подавать несколько ставок в разные лоты

| Поле         | Тип       | Nullable     | Описание                               |
|--------------|-----------|--------------|----------------------------------------|
| id           | UUID      | no           | Уникальный идентификатор подрядчика    |
| email        | string    | no, unique   | Электронная почта подрядчика           |
| first_name   | string    | no           | Имя                                    |
| second_name  | string    | no           | Фамилия                                |
| patronymic   | string    | yes          | Отчество (необязательно)               |
| agreement_id | UUID      | no           | Идентификатор договора/соглашения      |
| created_at   | timestamp | no           | Дата и время создания записи           |
| updated_at   | timestamp | yes          | Дата и время последнего обновления записи |

#### 2️⃣ CargoType (Тип груза)

Справочная сущность, определяющая тип перевозимого груза.

Используется для:

- классификации лотов
- потенциального расширения бизнес-логики (например, ограничения по транспорту)

| Поле       | Тип       | Nullable   | Описание                                           |
|------------|-----------|------------|----------------------------------------------------|
| id         | UUID      | no         | Уникальный идентификатор типа груза                |
| name       | string    | no, unique | Наименование груза (например, "Семена подсолнечника") |
| created_at | timestamp | no         | Дата создания записи                               |
| updated_at | timestamp | yes        | Дата последнего обновления                         |

#### 3️⃣ Lot (Лот)

Ключевая агрегатная сущность системы.

Лот представляет собой:

- фиксированный объём груза, доступный для распределения
- временное окно торгов (opens_at / closes_at)
- параметры аукциона (начальная цена, шаг)

Лот управляет:

- текущим распределением объема (reserved_volume)
- жизненным циклом торгов (CREATED → OPEN → CLOSED)
- инвариантами системы

| Поле            | Тип       | Nullable   | Описание                                              |
|-----------------|-----------|------------|-------------------------------------------------------|
| id              | UUID      | no         | Уникальный идентификатор лота                         |
| cargo_type_id   | UUID      | no         | FK на таблицу CargoType — какой тип груза перевозится |
| total_volume    | int       | no         | Общий объём лота                                      |
| reserved_volume | int       | no, default 0 | Суммарный объём уже распределённый по ставкам      |
| start_price     | int       | no         | Начальная ставка за единицу объёма (цена за тонну в копейках) |
| price_step      | int       | no         | Шаг изменения ставки при торгах (в копейках)          |
| status          | enum      | no         | Жизненный цикл лота: CREATED, OPEN, CLOSED            |
| opens_at        | timestamp | no         | Время открытия лота для торгов                        |
| closes_at       | timestamp | no         | Время окончания торгов                                |
| close_reason    | enum      | yes        | Причина закрытия: EXPIRED, MANUAL                     |
| volume_step_id  | enum      | no         | грузоподъёмность машин (volume_step)                  |
| created_at      | timestamp | no         | Дата создания лота                                    |
| updated_at      | timestamp | yes        | Дата последнего обновления                            |

**Embeddable:**

- `Volume(total_volume, reserved_volume, volume_step_id)`
- `Price(start_price, price_step)`
- `LotTermination(closes_at, close_reason)`

#### 5️⃣ Bid (Ставка)

Основная операционная сущность системы.

Ставка:

- подаётся подрядчиком в рамках конкретного лота
- содержит желаемый объем (requested_volume)
- содержит фактически выделенный объём (allocated_volume)
- участвует в перераспределении при поступлении новых ставок

**Ключевая особенность:**

- `allocated_volume` может изменяться со временем (ставка может быть частично или полностью вытеснена)

| Поле               | Тип       | Nullable   | Описание                                          |
|--------------------|-----------|------------|---------------------------------------------------|
| id                 | UUID      | no         | Уникальный идентификатор ставки                   |
| lot (lot_id)       | UUID      | no         | FK на Lot                                         |
| contractor (contractor_id) | UUID | no       | FK на Contractor                                  |
| requested_volume   | int       | no         | Объём, который хочет зарезервировать подрядчик    |
| allocated_volume   | int       | no, default 0 | Фактически выделенный объём после перераспределений |
| price_per_demension| int       | no         | Цена за единицу объёма (тонну) в копейках         |
| status             | enum      | no         | Состояние ставки: ACTIVE, OUTBID, PARTIALLY_ACTIVE, CANCELLED |
| rejection_reason   | string    | yes        | Причина отклонения ставки (если есть)             |
| created_at         | timestamp | no         | Дата создания ставки                              |
| updated_at         | timestamp | yes        | Дата последнего обновления                        |

### 🔗 Связи между сущностями

- `Contractor` 1 → N `Bid`
- `Lot` 1 → N `Bid`
- `Lot` → `CargoType` (N:1)
- `Lot` → `VolumeStep` (N:1)

### 🧠 Ключевая идея модели

Система не хранит "финальный результат" сразу.

Вместо этого:

- хранится текущее распределение (allocated_volume)
- при каждой новой ставке происходит перераспределение
- более дешевые ставки вытесняют более дорогие

Таким образом, модель реализует **динамический аукцион с приоритетом по цене**, где:

- лучшая (дешевая) цена получает приоритет. (Более дешёвые ставки вытесняют более дорогие)
- Если цены равны, приоритет получает ставка, поданная раньше (Более поздние ставки с той же ценой имеют меньший приоритет и вытесняются первыми)
- объем перераспределяется в реальном времени
- финальное состояние фиксируется при закрытии лота

---

## Алгоритм распределения объема при новой ставке

```php
// ПСЕВДОКОД!!!

$remaining = $newBid->requested_volume;

// 1. Сначала занимаем свободный объём лота
$free = getFreeVolume($lotId); // free = lot.total_volume - lot.reserved_volume
$takeFromFree = min($remaining, $free); // сколько можно сразу разместить
$remaining -= $takeFromFree;

// 2. Если ещё осталось -- вытесняем ставки с худшей ценой
if ($remaining > 0) {
    // Берём все ставки с price_per_unit > newBid->price_per_unit и allocated_volume > 0
    $bids = getBidsWorseThan($lotId, $newBid->price_per_unit);

    foreach ($bids as $bid) {
        if ($remaining <= 0) break;

        if ($bid->allocated_volume <= $remaining) {
            $remaining -= $bid->allocated_volume;
            $bid->allocated_volume = 0;
            $bid->status = 'OUTBID';
        } else {
            $bid->allocated_volume -= $remaining;
            $remaining = 0;
            $bid->status = 'PARTIALLY_ACTIVE';
        }

        save($bid);
    }
}

// 3. Выделяем объём новой ставке
$newBid->allocated_volume = $newBid->requested_volume - $remaining;

if ($newBid->allocated_volume > 0) {
    $newBid->status = 'ACTIVE';
    save($newBid);

    return
}

$newBid->status = 'REJECTED';
save($newBid);

// 4. Обновляем reserved_volume лота
updateLotReservedVolume($lotId);
```

**Пояснения:**

- `getFreeVolume` — возвращает сколько ещё объёма не занято (total_volume - reserved_volume).
- `getBidsWorseThan` — выбирает ставки с ценой выше новой ставки и с allocated_volume > 0.
- `$remaining` — показывает, сколько ещё объёма нужно разместить после свободного объёма.
- **Вытеснение** — перераспределяет объём: сначала полностью вытесняем худшие, потом частично.
- **Статусы ставок** помогают UI и истории, но главный критерий — allocated_volume.

---

## Алгоритм завершения торгов и определения победителей

### 1. Триггер закрытия лота

Лот переходит в статус `CLOSED` при выполнении одного из условий:

- Наступление времени `closes_at`
- Истечение TTL лота
- Принудительное закрытие вручную или внешней системой

### 2. Основные шаги алгоритма закрытия

1. Лот блокируется для новых ставок (`SELECT ... FOR UPDATE` при реализации на БД).
2. Получаем все ставки для лота с `allocated_volume > 0`.
3. Определяем победителей:
   - Ставки сортируются по `price_per_unit ASC` (меньше цена — выше приоритет)
   - При равной цене — по `created_at ASC` (раньше поданная ставка — выше приоритет)
4. Резервированные объёмы закрепляются окончательно в `allocated_volume`.
5. Генерируются доменные события:
   - `LotClosed` — лот закрыт
   - `LotFullyReserved` — если весь объём распределён
   - `WinnerDeterminated` — победители определены (высылается в Kafka топик `contractor_winner`)

### 3. Валидации и ограничения

- Только лоты в статусе `OPEN` могут быть закрыты через данный механизм
- Инварианты `allocated_volume ≤ total_volume` должны соблюдаться
- Ставки с `allocated_volume = 0` не попадают в список победителей

---

## 🧾 Prompt: Реализация POST /api/lot

Реализуй HTTP endpoint `POST /api/lot` в Symfony (CQRS/DDD), который создаёт новый лот для проведения торгов. Помни, необходим результат с низкой цикломатической сложностью. Предпочитается early return вместо else. Нацеливайся на уменьшение вложенности

### 📥 Входные данные

```json
{
  "cargo_type_id": "uuid",
  "total_volume": 100,
  "start_price": 1000,
  "price_step": 100,
  "volume_step_id": "uuid",
  "opens_at": "timestamp",
  "closes_at": "timestamp"
}
```

### 🎯 Требования к логике

#### 1. Первичная валидация входящих данных

- `cargo_type_id` корректный uuid
- `volume_step_id` корректный uuid
- `total_volume > 0`
- `start_price > 0`
- `price_step > 0`
- `opens_at < closes_at`
- `closes_at > now()`

#### 2. Создание лота

Создать новый Lot:

```php
$lot = new Lot(
    id: UUID,
    cargoType: $cargoType,
    totalVolume: $totalVolume,
    reservedVolume: 0,
    startPrice: $startPrice,
    priceStep: $priceStep,
    status: 'CREATED',
    opensAt: $opensAt,
    closesAt: $closesAt,
    volumeStep: $volumeStep,
    version: 1,
    createdAt: now()
);
```

#### 3. Бизнес-правила

- Лот создаётся в статусе `CREATED`
- Резервированный объём всегда начинается с 0
- Торги начинаются только после `opens_at`
- Закрытие происходит:
  - автоматически по `closes_at`
  - или вручную (в будущем)
- У нас есть первичная валидация, при необходимости добавляем вторичную валидацию, н-р указанных во входных данных тип груза реально существует в системе (справочник) и т.д

#### 5. Ответ

```json
{
  "lot_id": "uuid",
  "status": "CREATED"
}
```

### 🧠 Важные замечания

- Лот — это агрегат (Aggregate Root)
- Все ставки (Bid) всегда создаются в контексте лота
- Все конкурентные операции происходят только внутри одного лота
- `reserved_volume` обновляется только через операции со ставками

---

## 🧾 Prompt: Реализация POST /api/bid

Реализуй HTTP endpoint `POST /api/bid` в Symfony (CQRS/DDD), который обрабатывает создание ставки (Bid) с конкурентным перераспределением объема. Помни, необходим результат с низкой цикломатической сложностью. Предпочитается early return вместо else. Нацеливайся на уменьшение вложенности

### 📥 Входные данные

```json
{
  "lot_id": "uuid",
  "requested_volume": 50,
  "price_per_unit": 900
}
```

**Заголовок:**

```
x-user-id: <contractor_id>
```

### 🎯 Требования к логике

#### 1. Первичная валидация входящих данных

- `requested_volume` кратен `volume_step`
- `requested_volume > 0`
- `price_per_unit > 0`

#### 2. Вторичная валидация

- Лот существует
- Лот в статусе `OPEN`
- Текущее время в диапазоне `opens_at <= now <= closes_at`

#### 2. Транзакция

Вся логика должна выполняться в одной транзакции с использованием пессимистической блокировки

```sql
SELECT * FROM lot WHERE id = :lot_id FOR UPDATE;
```

#### 3. Создание ставки

Создать новую запись bid:

- `requested_volume`
- `allocated_volume = 0`
- `price_per_unit`
- `status = PENDING`
- `contractor_id` из `x-user-id`

#### 4. Алгоритм распределения

Реализовать следующий алгоритм:

```php
$remaining = $newBid->requested_volume;

// 1. Сначала занимаем свободный объём
$free = $lot->total_volume - $lot->reserved_volume;

$takeFromFree = min($remaining, $free);
$remaining -= $takeFromFree;

// 2. Если нужно -- вытесняем худшие ставки
if ($remaining > 0) {
    $bids = SELECT * FROM bid
            WHERE lot_id = :lot_id
            AND price_per_unit > :new_price
            AND allocated_volume > 0
            ORDER BY price_per_unit DESC, created_at DESC
            FOR UPDATE;

    foreach ($bids as $bid) {
        if ($remaining <= 0) break;

        if ($bid->allocated_volume <= $remaining) {
            $remaining -= $bid->allocated_volume;
            $bid->allocated_volume = 0;
            $bid->status = 'OUTBID';
        } else {
            $bid->allocated_volume -= $remaining;
            $remaining = 0;
            $bid->status = 'PARTIALLY_ACTIVE';
        }

        save($bid);
    }
}
```

#### 5. Завершение обработки новой ставки

```php
$newBid->allocated_volume = $newBid->requested_volume - $remaining;

if ($newBid->allocated_volume > 0) {
    if ($newBid->allocated_volume < $newBid->requested_volume) {
        $newBid->status = 'PARTIALLY_ACTIVE';
    } else {
        $newBid->status = 'ACTIVE';
    }
} else {
    $newBid->status = 'REJECTED';
}
```

#### 6. Обновление лота

```php
$lot->reserved_volume = SUM(allocated_volume WHERE lot_id = :lot_id);
```

или инкрементально (предпочтительно)

#### 7. Commit транзакции

### ⚠️ Конкурентность

Использовать `SELECT ... FOR UPDATE` для:

- строки lot
- вытесняемых bid

При ошибках (deadlock, serialization_failure) реализовать retry (2–3 попытки)

### 📤 Ответ

```json
{
  "bid_id": "uuid",
  "allocated_volume": 50,
  "status": "ACTIVE"
}
```

### 🧠 Важные правила

**Приоритет ставок:**

- ниже цена → выше приоритет
- при равной цене → раньше созданная ставка выше

**Вытесняются:**

- сначала самые дорогие
- при равной цене — самые новые

### 🚀 Дополнительно (опционально)

- Поддержка request_id для идемпотентности
- Генерация доменных событий:
  - `ReservationSucceeded`

---

## 🏆 Prompt: Консольная команда завершения торгов "trade:calculate-winners"

### Название команды

```bash
php bin/console trade:calculate-winners
```

### Описание команды

Команда запускается регулярно через Kubernetes CronJob

Для каждого открытого лота:

1. Проверяет, что `closes_at <= now()`
2. Блокирует лот и связанные ставки для консистентного расчёта
3. Выполняет алгоритм распределения объёма и определения победителей (см. выше)
4. Обновляет статус лота на `CLOSED`
5. Генерирует события:
   - `LotClosed`
   - `LotFullyReserved` (если весь объём распределён)
   - `WinnerDeterminated` (с деталями победителей и их выделенным объёмом)
6. Публикует события в Kafka топик `contractor_winner` для сервисов-подписчиков события (например, сервис нотификации)

### Пример вывода команды

```
[INFO] Processing Lot: 123e4567-e89b-12d3-a456-426614174000
[INFO] Allocated volumes updated
[INFO] Lot status updated to CLOSED
[INFO] WinnerDeterminated event published to contractor_winner
[INFO] Finished processing 1 lot
```

### Концептуальный алгоритм команды - trade:calculate-winners

**Псевдокод:**

```php
<?php

// Symfony command: php bin/console trade:close-lots

$lots = $lotRepository->findLotsToClose($now);

foreach ($lots as $lot) {
    $db->transaction(function () use ($lot, $lotRepository, $bidRepository, $eventBus, $now) {
        // 1. Блокируем лот, чтобы никто не изменил его состояние во время закрытия
        $lot = $lotRepository->lockForUpdate($lot->id);

        if ($lot === null) {
            return;
        }

        if ($lot->status !== 'OPEN') {
            return;
        }

        if ($lot->closes_at > $now) {
            return;
        }

        // 2. Забираем уже распределённые ставки в порядке приоритета
        // ORDER BY price_per_unit ASC, created_at ASC
        $bids = $bidRepository->findAllocatedBidsForLotForUpdate($lot->id);

        // 3. Формируем список победителей
        $winners = [];
        foreach ($bids as $bid) {
            if ($bid->allocated_volume <= 0) {
                continue;
            }

            $winners[] = [
                'bid_id' => $bid->id,
                'contractor_id' => $bid->contractor_id,
                'allocated_volume' => $bid->allocated_volume,
                'price_per_unit' => $bid->price_per_unit,
            ];
        }

        // 4. Закрываем лот
        $lot->status = 'CLOSED';
        $lotRepository->save($lot);

        // 5. Публикуем доменные события
        $eventBus->publish(new LotClosed($lot->id));

        if ($lot->reserved_volume >= $lot->total_volume) {
            $eventBus->publish(new LotFullyReserved($lot->id));
        }

        if ($winners !== []) {
            $eventBus->publish(new WinnerDeterminated($lot->id, $winners));
        }
    });
}
```
