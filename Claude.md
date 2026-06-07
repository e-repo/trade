# Symfony CQRS/DDD Project Template Reference

## Tech Stack
- **PHP 8.4.19** with strict types
- **Symfony 7.4** (LTS)
- **PostgreSQL 18** with schema-per-module
- **Doctrine ORM 3.6**
- **Symfony Messenger** (CQRS buses)
- **Docker** (nginx, PHP-FPM, PHP-CLI, PostgreSQL, MailHog)

## Core Architecture Principles

### 1. Modular Architecture with Low Coupling
- Modules interact **exclusively via EventBus**
- Designed for future microservice extraction
- Each module has its own PostgreSQL schema
- **Rule**: Modules CANNOT directly use classes from other modules (except CoreKit)

### 2. Four-Layer Module Structure
```
Module/
├── UI/                    # HTTP Actions (Controllers), Request/Response DTOs
├── Application/           # Use Cases: Commands, Queries, Listeners, Services
├── Domain/                # Business Logic: Entities, Repository Interfaces
└── Infra/                 # Technical Implementations: Repositories, Fetchers
```
**Dependency Flow**: UI → Application → Domain ← Infra

### 3. CQRS with Three Message Buses
1. **command.bus** - Write operations (synchronous, transactional)
2. **query.bus** - Read operations (synchronous)
3. **event.bus** - Inter-module communication (asynchronous, no handlers required)

**Handler Convention**:
- All handlers implement `CommandHandlerInterface`, `QueryHandlerInterface`, or `EventListenerInterface`
- Auto-tagged via `_instanceof` in services.yaml
- Handler file always named `Handler.php` with `__invoke()` method

## Project Structure

### Current Modules
- `Trade` - Trade auction service (main business module)
- `SomeModule` - Example module with Category aggregate (will be removed)
- `CoreKit` - Shared utilities and interfaces for all modules

### Module Namespaces (composer.json)
```json
"autoload": {
    "psr-4": {
        "Trade\\": "src/Trade",
        "SomeModule\\": "src/SomeModule",
        "CoreKit\\": "src/CoreKit"
    }
}
```

### Database Schemas
- `trade` - Trade module tables
- `module` - SomeModule tables
- `corekit` - CoreKit shared tables
- Migrations organized by module in `migrations/{Module}/`

### Trade Module Structure
```
Trade/
├── UI/
│   ├── Http/V1/
│   │   ├── Lot/Create/        # POST /api/v1/trade/lot - Create lot
│   │   ├── Lot/Get/           # GET /api/v1/trade/lot/{lotId} - Get lot with winners
│   │   └── Bid/PlaceBid/      # POST /api/v1/trade/bid - Place bid
│   └── Console/
│       ├── OpenLotsCommand.php           # trade:open-lots
│       └── CalculateWinnersCommand.php   # trade:calculate-winners
├── Application/
│   ├── Lot/Command/           # Create, Open, CloseDueLots
│   ├── Lot/Query/             # Get
│   └── Bid/Command/           # PlaceBid, CalculateAllocations
├── Domain/
│   ├── Dictionary/       # Reference entities
│   │   ├── Entity/       # CargoType, Contractor, VolumeStep
│   │   └── Repository/   # Repository interfaces
│   └── Lot/              # Lot aggregate (Lot + Bid entities)
│       ├── Entity/       # Lot, Bid
│       ├── Repository/   # LotRepositoryInterface
│       ├── Enum/         # LotStatusEnum, CloseReasonEnum
│       └── ValueObject/  # Volume, Price, LotTermination
└── Infra/
    ├── Dictionary/Repository/ # Dictionary repository implementations
    └── Lot/Repository/        # LotRepository (with N+1 optimization)
```

### Trade Module Features

**Business Logic:**
- Reverse auction system for cargo transportation
- Lot lifecycle: PENDING → OPEN → CLOSED
- Automatic lot opening/closing by schedule
- Winner calculation with volume allocation algorithm
- Pessimistic locking (SELECT FOR UPDATE) for concurrency safety

**Key Endpoints:**
- Create lot with volume, price, schedule
- Place bids with price per ton and desired volume
- Get lot details with winner contractor IDs (empty array if not closed)

**Console Commands:**
- `trade:open-lots` - Opens lots when opensAt time arrives
- `trade:calculate-winners` - Closes expired lots and calculates winners with batch processing (100 lots per batch)

**Performance Optimizations:**
- `findLotsToCloseIterator` uses 3-step batch loading to eliminate N+1 queries:
  1. SELECT lot IDs with FOR UPDATE
  2. Load Lot entities via Doctrine
  3. Load allocated bids with single IN query
- Result: 3 queries per batch instead of N+1

## Code Patterns & Examples

### HTTP Endpoint Pattern (Actions)
```
UI/Http/V1/{Aggregate}/{Action}/
├── Action.php          # Controller with Route, Security, OpenAPI annotations
├── Request.php         # DTO with validation constraints
└── Response.php        # Response DTO
```

**Example: Create Category**
```php
// UI/Http/V1/Category/Create/Action.php
#[Route('/api/v1/module/category', methods: ['POST'])]
#[OA\Post(summary: 'Create category')]
final class Action extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    #[Route(path: '/api/v1/module/category', name: 'module_create-category', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $this->commandBus->dispatch(
            new Command(
                name: $request->name,
                description: $request->description,
            )
        );

        return new JsonResponse(
            new ResponseWrapper(
                data: new Response('Category created successfully.')
            )
        );
    }
}
```

### Command Pattern
```
Application/{Aggregate}/Command/{Action}/
├── Command.php
└── Handler.php
```

**Example: Create Command**
```php
// Application/Category/Command/Create/Command.php
final readonly class Command
{
    public function __construct(
        public string $name,
        public string $description,
    ) {}
}

// Application/Category/Command/Create/Handler.php
final readonly class Handler implements CommandHandlerInterface
{
    public function __construct(
        private CategoryRepositoryInterface $repository,
        private SpecificationAggregator $specificationAggregator,
    ) {}

    public function __invoke(Command $command): void
    {
        $category = new Category(
            categoryDto: new CategoryDto(
                name: $command->name,
                description: $command->description,
            ),
            specificationAggregator: $this->specificationAggregator,
        );

        $this->repository->add($category);
    }
}
```

### Query Pattern
```
Application/{Aggregate}/Query/{Operation}/
├── Query.php
├── Result.php
└── Handler.php
```

### Entity Pattern (Domain)
```php
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\Table(schema: 'module')]
class Category
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Id $id;

    #[ORM\Column(length: 50, unique: true)]
    private string $name;

    public function __construct(
        CategoryDto $categoryDto,
        SpecificationAggregator $specificationAggregator,
    ) {
        $this->id = $categoryDto->id === null
            ? Id::next()
            : new Id($categoryDto->id);

        $this->name = $categoryDto->name;
        $this->checkSpecifications($specificationAggregator);
    }

    private function checkSpecifications(SpecificationAggregator $aggregator): void
    {
        if (!$aggregator->uniqueNameSpecification->isSatisfiedBy($this)) {
            throw new DomainException('Category with this name already exists.');
        }
    }
}
```

### Repository Pattern
```php
// Domain/Post/Repository/CategoryRepositoryInterface.php
interface CategoryRepositoryInterface
{
    public function add(Category $category): void;
    public function get(Id $id): Category;
}

// Infra/Post/Repository/CategoryRepository.php
final class CategoryRepository implements CategoryRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function add(Category $category): void
    {
        $this->em->persist($category);
    }
}
```

## CoreKit Utilities

### Custom Types
- **`Id`** - UUID value object (used for all entity primary keys)
  ```php
  $id = Id::next(); // Generate new UUID
  $id = new Id($uuidString); // From existing UUID
  ```

### Buses (Interfaces)
- `CommandBusInterface` - Dispatch commands
- `QueryBusInterface` - Dispatch queries
- `EventBusInterface` - Publish events

### Response Wrapper
All API responses use `ResponseWrapper`:
```json
{
  "data": { /* response data */ },
  "violations": [
    {
      "source": "field_name",
      "detail": "Error message",
      "data": {}
    }
  ]
}
```

### Custom Validator
- `@NotWhitespace` - Prevents whitespace-only strings

### Exception Handling
- `DomainException` → HTTP 422 (business logic violations)
- `NotFoundException` → HTTP 404
- `AccessDeniedException` → HTTP 403
- Exceptions mapped in `config/packages/exceptions.yaml`

## Coding Standards

### File Naming
- **Commands**: `{Action}Command.php` (e.g., `CreateCommand.php`)
- **Queries**: `{Operation}Query.php` (e.g., `GetListQuery.php`)
- **Handlers**: Always `Handler.php`
- **Actions**: Always `Action.php`
- **Repositories**: `{Entity}Repository.php` (interface in Domain, implementation in Infra)

### Code Style
- PHP 8.4+ with `declare(strict_types=1);`
- PSR-12 standard, checked by ECS
- Pre-commit hooks via CaptainHook
- Readonly properties for immutable DTOs
- UUID primary keys via custom `Id` type
- Named arguments preferred for clarity

### Validation
- Request DTOs use Symfony Validator constraints
- Custom validator: `@NotWhitespace`
- Validation messages in Russian (`translations/validators.ru.yaml`)

### Best Practices
- **Low cyclomatic complexity** - prefer early returns over else
- **Reduce nesting** - keep code flat
- **Avoid over-engineering** - only implement what's requested
- **No backwards-compatibility hacks** - delete unused code completely

## Adding New Features

### To Existing Module
1. Define aggregate (e.g., Post, Bid, Lot)
2. Create use case structure:
   ```
   Application/{Aggregate}/Command/{Action}/
   ├── Command.php
   └── Handler.php
   ```
3. Add HTTP endpoint in `UI/Http/V1/{Aggregate}/{Action}/`
4. Update domain if needed (Entity, Repository interface)
5. Implement infrastructure (Repository methods in Infra)
6. Write tests in `tests/Test/Integration/`

### Create New Module
1. Create directory structure: `src/{Module}/{UI,Application,Domain,Infra}`
2. Register namespace in `composer.json` autoload
3. Register services in `config/services.yaml`:
   ```yaml
   {Module}\:
       resource: '../src/{Module}'
       exclude:
           - '../src/{Module}/{Kernel.php,*Dto.php}'
           - '../src/{Module}/{Entity,Enum,Event}'
   ```
4. Create database schema: `{module_name}` in migrations
5. Follow low-coupling rule: interact with other modules only via EventBus

## Database & Migrations

### Schema-per-Module
Each module owns its PostgreSQL schema:
```sql
SET search_path TO module;
```

### Migration Workflow
```bash
php bin/console do:mi:diff                          # Generate migration
# Edit migration to set schema: $this->addSql('SET search_path TO module');
php bin/console do:mi:mi --no-interaction           # Apply migrations
```

### Common Commands
```bash
php bin/console do:mi:mi --no-interaction           # Apply migrations
php bin/console do:mi:gene                          # Create empty migration
php bin/console doctrine:schema:validate            # Validate schema
php bin/console doctrine:query:sql "SELECT * FROM module.category"
```

## Testing

### Test Base Class
Tests extend `FunctionalTestCase` from `tests/Test/Common/`:
- Uses `DatabaseToolCollection` for fixtures
- Fixtures named `{Entity}Fixture.php`
- DAMA Doctrine Bundle provides transactional isolation
- Test database auto-created via `docker/development/create-test-database.sh`

### Test Structure
```php
public function test_success(): void
{
    // Arrange
    $this->loadFixtures([CategoryFixture::class]);

    // Act
    $response = $this->sendRequest('POST', '/api/v1/module/category', $data);

    // Assert
    $this->assertResponseIsSuccessful();
}
```

### Test Commands
```bash
php bin/phpunit                                     # Run all tests
php bin/phpunit --filter=CreateCategoryTest         # Run specific test
php bin/phpunit --testsuite=Integration             # Run integration tests
```

## Docker & Development

### Quick Start
```bash
make be-init              # Full initialization: build, install deps, migrations
make b-up                 # Start all backend services
make b-shell              # Enter CLI container (sh)
make down                 # Stop all containers
make ps                   # Show running containers status
```

### Service Access
- **Application**: http://localhost:8088
- **API Documentation**: http://localhost:8088/api/doc
- **MailHog**: http://localhost:8025
- **PostgreSQL**: localhost:54321 (user: app, db: app)

### Development Commands (from Host)
```bash
# Container management
make b-up                          # Start backend services (postgres, php-fpm, php-cli, nginx, mailer)
make down                          # Stop all containers
make ps                            # Show container status
make b-shell                       # Enter CLI container

# Setup & Installation
make be-init                       # Full project initialization (pull, build, up, install, migrations)
make t-composer-install            # Install composer dependencies
make t-wait-db                     # Wait for database to be ready

# Database
make t-migrations                  # Apply all pending migrations
docker compose run --rm php-cli php bin/console do:mi:diff              # Generate new migration
docker compose run --rm php-cli php bin/console doctrine:schema:validate

# Testing
make t-test                        # Run all tests via PHPUnit
docker compose run --rm php-cli php bin/phpunit --filter=SomeTest      # Run specific test

# Permissions
make b-chown                       # Fix file ownership (sets to UID 1000)
```

### Inside CLI Container (after `make b-shell`)
```bash
php bin/console cache:clear                         # Clear cache
php bin/phpunit                                     # Run tests
php bin/console do:mi:mi --no-interaction           # Apply migrations
php bin/console do:mi:diff                          # Generate migration
php bin/console doctrine:query:sql "SELECT * FROM trade.cargo_type"
```

## Code Quality

### ECS (Easy Coding Standard)
```bash
vendor/bin/ecs check src                # Check code style
vendor/bin/ecs check src --fix          # Auto-fix issues
```

### Pre-commit Hooks
CaptainHook runs automatically on `git commit` to enforce standards.

## Key Configuration Files

### Messenger Buses (`config/packages/messenger.yaml`)
```yaml
framework:
    messenger:
        buses:
            command.bus:
                middleware:
                    - doctrine_ping_connection
                    - doctrine_transaction
            query.bus: ~
            event.bus:
                default_middleware:
                    allow_no_handlers: true
```

### Service Registration (`config/services.yaml`)
```yaml
_instanceof:
    CoreKit\Application\Bus\CommandHandlerInterface:
        tags:
            - { name: messenger.message_handler, bus: command.bus }
    CoreKit\Application\Bus\QueryHandlerInterface:
        tags:
            - { name: messenger.message_handler, bus: query.bus }
    CoreKit\Application\Bus\EventListenerInterface:
        tags:
            - { name: messenger.message_handler, bus: event.bus }
```

## Important Notes

### Authentication
- Template does NOT include authentication
- Assumes external Gateway service handles authorization
- Use `x-user-id` header for user identification (configured in Swagger UI)

### Concurrency
- Use pessimistic locking for concurrent operations: `SELECT ... FOR UPDATE`
- Implement retry logic for deadlocks/serialization failures
- Transactions managed automatically by command.bus middleware

### Environment Variables
- `clear_env = no` in `docker/development/fpm/pool.d/www.conf`
- All container env vars available in PHP
- Key vars: `DATABASE_URL`, `APP_ENV`, `APP_SECRET`

### Debugging
- Xdebug enabled in CLI and FPM containers
- IDE server name: `bb.local`
- Use `dd()` or `dump()` from Symfony VarDumper
- Logs: `var/log/dev.log`

## Module Communication Example

### Publishing Events
```php
// In Command Handler
$this->eventBus->publish(
    new LotCreatedEvent($lot->getId(), $lot->getTotalVolume())
);
```

### Consuming Events
```php
// In another module's Listener
final readonly class LotCreatedListener implements EventListenerInterface
{
    public function __invoke(LotCreatedEvent $event): void
    {
        // React to event from another module
    }
}
```

## Reference Entity: Category

**Location**: `src/SomeModule/Domain/Post/Entity/Category.php`

**Key Features**:
- UUID primary key via `Id` value object
- Schema: `module`
- DTO-based construction
- Specification pattern for business rules
- Immutable timestamps
- Doctrine annotations for ORM mapping

This Category entity serves as the reference implementation for the project's entity patterns and should be used as a template when creating new entities.
