# Руководство по ручному тестированию Trade API

Инструкция для тестирования полного цикла торгов: создание лота → размещение ставок → открытие торгов → определение победителей.

## Предварительная подготовка

### 1. Проверка Swagger UI
Откройте API документацию: http://localhost:8088/api/doc

### 2. Доступные данные в БД (из seed-миграций)

**Тип груза:**
- ID: `550e8400-e29b-41d4-a716-446655440001`
- Название: "Семена подсолнечника"

**Шаг объёма:**
- ID: `550e8400-e29b-41d4-a716-446655440010`
- Значение: 25 тонн

**Контрагенты (для тестирования):**
- Контрагент #1: `550e8400-e29b-41d4-a716-446655440020` (Иван Иванов)
- Контрагент #2: `550e8400-e29b-41d4-a716-446655440021` (Пётр Петров)
- Контрагент #3: `550e8400-e29b-41d4-a716-446655440022` (Сергей Сергеев)

---

## Сценарий 1: Полный цикл торгов (Happy Path)

### Шаг 1: Создать лот

**Описание:** Создаём лот на перевозку 1000 тонн груза

**Запрос:**
```bash
curl -X POST http://localhost:8088/api/v1/trade/lot \
  -H "Content-Type: application/json" \
  -d '{
    "cargoTypeId": "550e8400-e29b-41d4-a716-446655440001",
    "totalVolume": 1000,
    "startPrice": 50000,
    "priceStep": 1000,
    "volumeStepId": "550e8400-e29b-41d4-a716-446655440010",
    "opensAt": '$(date -v+1M +%s)',
    "closesAt": '$(date -v+10M +%s)'
  }'
```

**Ожидаемый ответ (HTTP 200):**
```json
{
  "data": {
    "lotId": "550e8400-e29b-41d4-a716-446655440999",
    "status": "PENDING"
  },
  "violations": []
}
```

**💡 Сохраните lotId из ответа - он понадобится для следующих шагов!**

---

### Шаг 2: Открыть лот на торги

**Описание:** Переводим лот из статуса PENDING в OPEN (делает консольная команда автоматически по расписанию)

**Команда:**
```bash
docker compose run --rm php-cli php bin/console trade:open-lots
```

**Ожидаемый вывод:**
```
Successfully opened 1 lot(s).
```

**Проверка статуса лота:**
```bash
curl -X GET http://localhost:8088/api/v1/trade/lot/{lotId}
```

Статус должен быть `"OPEN"`.

---

### Шаг 3: Разместить ставку от первого контрагента

**Описание:** Контрагент #1 (Иван Иванов) делает ставку: 600 тонн по 48000 коп/тонну

**Запрос:**
```bash
curl -X POST http://localhost:8088/api/v1/trade/bid \
  -H "Content-Type: application/json" \
  -H "x-user-id: 550e8400-e29b-41d4-a716-446655440020" \
  -d '{
    "lotId": "{lotId}",
    "contractorId": "550e8400-e29b-41d4-a716-446655440020",
    "requestedVolume": 600,
    "pricePerTon": 48000
  }'
```

**Ожидаемый ответ (HTTP 200):**
```json
{
  "data": {
    "bidId": "...",
    "status": "ACTIVE",
    "allocatedVolume": 600,
    "requestedVolume": 600
  },
  "violations": []
}
```

---

### Шаг 4: Разместить ставку от второго контрагента (дешевле)

**Описание:** Контрагент #2 (Пётр Петров) делает более выгодную ставку: 500 тонн по 46000 коп/тонну

**Запрос (из другого браузера/терминала):**
```bash
curl -X POST http://localhost:8088/api/v1/trade/bid \
  -H "Content-Type: application/json" \
  -H "x-user-id: 550e8400-e29b-41d4-a716-446655440021" \
  -d '{
    "lotId": "{lotId}",
    "contractorId": "550e8400-e29b-41d4-a716-446655440021",
    "requestedVolume": 500,
    "pricePerTon": 46000
  }'
```

**Ожидаемый ответ (HTTP 200):**
```json
{
  "data": {
    "bidId": "...",
    "status": "ACTIVE",
    "allocatedVolume": 500,
    "requestedVolume": 500
  },
  "violations": []
}
```

**Результат:** Контрагент #2 получает приоритет из-за более низкой цены. Ставка контрагента #1 частично вытесняется.

---

### Шаг 5: Разместить ещё одну ставку от первого контрагента (доберёт остаток)

**Описание:** Контрагент #1 делает дополнительную ставку на оставшийся объём

**Запрос:**
```bash
curl -X POST http://localhost:8088/api/v1/trade/bid \
  -H "Content-Type: application/json" \
  -H "x-user-id: 550e8400-e29b-41d4-a716-446655440020" \
  -d '{
    "lotId": "{lotId}",
    "contractorId": "550e8400-e29b-41d4-a716-446655440020",
    "requestedVolume": 500,
    "pricePerTon": 47000
  }'
```

**Ожидаемый ответ:**
```json
{
  "data": {
    "bidId": "...",
    "status": "ACTIVE",
    "allocatedVolume": 500,
    "requestedVolume": 500
  },
  "violations": []
}
```

**Текущее состояние:**
- Контрагент #2: 500 тонн по 46000 коп/т (самая выгодная ставка)
- Контрагент #1: 500 тонн по 47000 коп/т
- Весь объём лота (1000 тонн) распределён

---

### Шаг 6: Изменить дату окончания торгов (для тестирования)

**Описание:** Переносим closesAt в прошлое, чтобы лот можно было закрыть

**SQL запрос (выполнить в БД):**
```bash
docker compose run --rm php-cli php bin/console doctrine:query:sql \
  "UPDATE trade.lot SET termination_closes_at = NOW() - INTERVAL '1 minute' WHERE id = '{lotId}'"
```

**Ожидаемый вывод:**
```
1 row(s) affected.
```

---

### Шаг 7: Закрыть торги и определить победителей

**Описание:** Команда закрывает просроченные лоты и определяет победителей

**Команда:**
```bash
docker compose run --rm php-cli php bin/console trade:calculate-winners
```

**Ожидаемый вывод:**
```
Successfully closed 1 lot(s).
```

---

### Шаг 8: Проверить результаты торгов

**Описание:** Получаем информацию о закрытом лоте с ID победителей

**Запрос:**
```bash
curl -X GET http://localhost:8088/api/v1/trade/lot/{lotId}
```

**Ожидаемый ответ (HTTP 200):**
```json
{
  "data": {
    "lotId": "{lotId}",
    "status": "CLOSED",
    "totalVolume": 1000,
    "startPrice": 50000,
    "priceStep": 1000,
    "opensAt": 1735689600,
    "closesAt": 1735776000,
    "closeReason": "EXPIRED",
    "winnerContractorIds": [
      "550e8400-e29b-41d4-a716-446655440021",
      "550e8400-e29b-41d4-a716-446655440020"
    ]
  },
  "violations": []
}
```

**Интерпретация:**
- Лот закрыт (status = CLOSED)
- Причина закрытия: истекло время (closeReason = EXPIRED)
- Победители (в порядке приоритета):
  1. Контрагент #2 (Пётр Петров) - 500 тонн по 46000 коп/т
  2. Контрагент #1 (Иван Иванов) - 500 тонн по 47000 коп/т

---

## Сценарий 2: Вытеснение дорогих ставок

### Шаг 1: Создать лот (аналогично Сценарию 1, Шаг 1)

### Шаг 2: Открыть лот (аналогично Сценарию 1, Шаг 2)

### Шаг 3: Разместить дорогую ставку

**Контрагент #1 ставит 1000 тонн по 50000 коп/т:**
```bash
curl -X POST http://localhost:8088/api/v1/trade/bid \
  -H "Content-Type: application/json" \
  -H "x-user-id: 550e8400-e29b-41d4-a716-446655440020" \
  -d '{
    "lotId": "{lotId}",
    "contractorId": "550e8400-e29b-41d4-a716-446655440020",
    "requestedVolume": 1000,
    "pricePerTon": 50000
  }'
```

**Результат:** allocated_volume = 1000 (весь лот зарезервирован)

### Шаг 4: Разместить дешевую ставку (полное вытеснение)

**Контрагент #2 ставит 1000 тонн по 45000 коп/т:**
```bash
curl -X POST http://localhost:8088/api/v1/trade/bid \
  -H "Content-Type: application/json" \
  -H "x-user-id: 550e8400-e29b-41d4-a716-446655440021" \
  -d '{
    "lotId": "{lotId}",
    "contractorId": "550e8400-e29b-41d4-a716-446655440021",
    "requestedVolume": 1000,
    "pricePerTon": 45000
  }'
```

**Результат:**
- Контрагент #2: allocated_volume = 1000 (победитель)
- Контрагент #1: allocated_volume = 0 (полностью вытеснен)

---

## Сценарий 3: Частичное вытеснение

### Шаг 1-2: Создать и открыть лот

### Шаг 3: Разместить ставку на весь объём

**Контрагент #1: 1000 тонн по 49000 коп/т**

### Шаг 4: Разместить ставку на половину объёма с более низкой ценой

**Контрагент #2: 500 тонн по 46000 коп/т**

**Результат:**
- Контрагент #2: allocated_volume = 500 (приоритет)
- Контрагент #1: allocated_volume = 500 (частично вытеснен, было 1000)

---

## Проверка событий в логах

**Файл:** `var/log/events.log`

**Просмотр событий:**
```bash
docker compose run --rm php-cli tail -f var/log/events.log
```

**Пример событий:**
```json
{"message":"Domain event published","context":{"event":"Trade\\Domain\\Event\\LotOpenedEvent","data":{"lotId":"...","openedAt":"2026-06-07T12:00:00+00:00"}}}
{"message":"Domain event published","context":{"event":"Trade\\Domain\\Event\\LotClosedEvent","data":{"lotId":"...","closeReason":"EXPIRED","closedAt":"2026-06-07T12:10:00+00:00"}}}
{"message":"Domain event published","context":{"event":"Trade\\Domain\\Event\\WinnerDeterminatedEvent","data":{"lotId":"...","winners":[...]}}}
```

---

## Полезные SQL запросы для отладки

### Просмотр всех лотов
```bash
docker compose run --rm php-cli php bin/console doctrine:query:sql \
  "SELECT id, status, volume_total_volume, price_start_price, termination_closes_at FROM trade.lot ORDER BY created_at DESC LIMIT 5"
```

### Просмотр ставок по лоту
```bash
docker compose run --rm php-cli php bin/console doctrine:query:sql \
  "SELECT contractor_id, requested_volume, allocated_volume, price_per_ton, status FROM trade.bid WHERE lot_id = '{lotId}' ORDER BY price_per_ton ASC, created_at ASC"
```

### Очистка данных (сброс)
```bash
docker compose run --rm php-cli php bin/console doctrine:query:sql "TRUNCATE trade.bid CASCADE"
docker compose run --rm php-cli php bin/console doctrine:query:sql "TRUNCATE trade.lot CASCADE"
```

---

## Советы по тестированию

1. **Unix timestamp генерация:**
   - Текущее время + 1 минута: `date -v+1M +%s` (macOS) или `date -d '+1 minute' +%s` (Linux)
   - Текущее время + 10 минут: `date -v+10M +%s` (macOS) или `date -d '+10 minutes' +%s` (Linux)

2. **Swagger UI (http://localhost:8088/api/doc):**
   - Нажмите "Authorize" и введите UUID контрагента в поле `x-user-id`
   - Используйте "Try it out" для тестирования endpoints через UI

3. **Два браузера для симуляции конкуренции:**
   - Браузер #1: установите `x-user-id` = `550e8400-e29b-41d4-a716-446655440020`
   - Браузер #2: установите `x-user-id` = `550e8400-e29b-41d4-a716-446655440021`

4. **Проверка событий в реальном времени:**
   ```bash
   docker compose run --rm php-cli tail -f var/log/events.log | jq .
   ```

---

## Типичные ошибки и их решения

### HTTP 400: Invalid lot ID format
- **Причина:** Невалидный UUID
- **Решение:** Проверьте формат UUID (36 символов с дефисами)

### HTTP 404: Lot not found
- **Причина:** Лот с таким ID не существует
- **Решение:** Используйте `lotId` из ответа создания лота

### HTTP 422: Domain exception
- **Причина:** Нарушение бизнес-правил (например, объём не кратен volumeStep)
- **Решение:** Проверьте параметры запроса (totalVolume должен быть кратен 25)

### Лот не открывается командой trade:open-lots
- **Причина:** Время `opensAt` ещё не наступило
- **Решение:** Используйте `opensAt` в прошлом или текущее время при создании лота

---

**Документация обновлена:** 2026-06-07
