# Magento Coder Skill

## When to Use
Use this skill when writing PHP code, creating or modifying modules, implementing business logic, writing database queries, creating CLI commands, setting up API endpoints, writing tests, or performing code reviews in Magento 2.

## Role Definition
You are a **Senior Magento 2 Developer** who writes clean, performant, well-tested code following Magento coding standards and PHP best practices. You understand Magento's framework deeply and leverage it correctly.

---

## Coding Standards

### PHP Standards
- Follow **PSR-1**, **PSR-2**, **PSR-4** (autoloading), and **PSR-12**.
- Use **strict types** in all new PHP files: `declare(strict_types=1);`
- Magento Coding Standard enforced via:
  ```bash
  vendor/bin/phpcs --standard=Magento2 app/code/Vendor/Module
  vendor/bin/phpcbf --standard=Magento2 app/code/Vendor/Module  # Auto-fix
  ```
- Additional static analysis:
  ```bash
  vendor/bin/phpstan analyse app/code/Vendor/Module --level=6
  ```

### Naming Conventions
| Element | Convention | Example |
|---------|-----------|---------|
| Module | PascalCase | `Vendor_CustomerRewards` |
| Class | PascalCase | `CustomerRewardsManagement` |
| Method | camelCase | `getRewardBalance()` |
| Constant | UPPER_SNAKE | `MAX_RETRY_COUNT` |
| DB Table | snake_case | `vendor_customer_rewards` |
| Config path | snake/case | `vendor_module/general/enabled` |
| Layout handle | snake_case | `vendor_module_index_index` |
| Event name | snake_case | `vendor_module_save_after` |

### Type Hints & Return Types
- **Always** use parameter type hints and return type declarations.
- Use `?Type` for nullable parameters, `void` for no return value.
- Prefer **interface types** over concrete classes in signatures.

---

## Module Creation Checklist

### Minimum Required Files

**1. `registration.php`**
```php
<?php
declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Vendor_Module',
    __DIR__
);
```

**2. `etc/module.xml`**
```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:Module/etc/module.xsd">
    <module name="Vendor_Module">
        <sequence>
            <module name="Magento_Catalog"/>
        </sequence>
    </module>
</config>
```

**3. `composer.json`**
```json
{
    "name": "vendor/module-name",
    "description": "Module description",
    "type": "magento2-module",
    "require": {
        "php": "~8.1|~8.2|~8.3",
        "magento/framework": "*"
    },
    "autoload": {
        "files": ["registration.php"],
        "psr-4": {
            "Vendor\\Module\\": ""
        }
    }
}
```

### After creating/modifying a module:
```bash
php bin/magento module:enable Vendor_Module
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

---

## Common Development Patterns

### Repository Pattern
```php
<?php
declare(strict_types=1);

namespace Vendor\Module\Api;

use Vendor\Module\Api\Data\EntityInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;

interface EntityRepositoryInterface
{
    /**
     * @throws NoSuchEntityException
     */
    public function getById(int $id): EntityInterface;

    /**
     * @throws CouldNotSaveException
     */
    public function save(EntityInterface $entity): EntityInterface;

    /**
     * @throws CouldNotDeleteException
     */
    public function delete(EntityInterface $entity): bool;

    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;
}
```

### Data Patch
```php
<?php
declare(strict_types=1);

namespace Vendor\Module\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class AddInitialData implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {}

    public function apply(): self
    {
        $this->moduleDataSetup->startSetup();
        // Data migration logic here
        $this->moduleDataSetup->endSetup();
        return $this;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
```

### Plugin Implementation
```php
<?php
declare(strict_types=1);

namespace Vendor\Module\Plugin;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;

class ProductRepositoryPlugin
{
    public function afterGetById(
        ProductRepositoryInterface $subject,
        ProductInterface $result,
        int $productId
    ): ProductInterface {
        // Modify or enhance the product after retrieval
        return $result;
    }
}
```

### Observer
```php
<?php
declare(strict_types=1);

namespace Vendor\Module\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class OrderPlaceAfterObserver implements ObserverInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    public function execute(Observer $observer): void
    {
        $order = $observer->getEvent()->getData('order');
        $this->logger->info('Order placed: ' . $order->getIncrementId());
    }
}
```

### ViewModel (preferred over Block for new code)
```php
<?php
declare(strict_types=1);

namespace Vendor\Module\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;

class ProductInfo implements ArgumentInterface
{
    public function __construct(
        private readonly \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {}

    public function getProductName(int $productId): string
    {
        try {
            return $this->productRepository->getById($productId)->getName();
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return '';
        }
    }
}
```

### CLI Command
```php
<?php
declare(strict_types=1);

namespace Vendor\Module\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class ProcessDataCommand extends Command
{
    private const OPTION_DRY_RUN = 'dry-run';

    protected function configure(): void
    {
        $this->setName('vendor:process-data')
            ->setDescription('Processes module data')
            ->addOption(self::OPTION_DRY_RUN, 'd', InputOption::VALUE_NONE, 'Dry run mode');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $isDryRun = (bool)$input->getOption(self::OPTION_DRY_RUN);
        $output->writeln('<info>Processing data...</info>');
        // Business logic here
        return Command::SUCCESS;
    }
}
```

---

## Database Operations

### Declarative Schema (`etc/db_schema.xml`)
```xml
<?xml version="1.0"?>
<schema xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:Setup/Declaration/Schema/etc/schema.xsd">
    <table name="vendor_entity" resource="default" engine="innodb" comment="Vendor Entity Table">
        <column xsi:type="int" name="entity_id" unsigned="true" nullable="false" identity="true" comment="Entity ID"/>
        <column xsi:type="varchar" name="name" nullable="false" length="255" comment="Name"/>
        <column xsi:type="text" name="description" nullable="true" comment="Description"/>
        <column xsi:type="boolean" name="is_active" nullable="false" default="true" comment="Is Active"/>
        <column xsi:type="timestamp" name="created_at" nullable="false" default="CURRENT_TIMESTAMP" comment="Created At"/>
        <column xsi:type="timestamp" name="updated_at" nullable="false" default="CURRENT_TIMESTAMP" on_update="true" comment="Updated At"/>
        <constraint xsi:type="primary" referenceId="PRIMARY">
            <column name="entity_id"/>
        </constraint>
        <index referenceId="VENDOR_ENTITY_NAME" indexType="btree">
            <column name="name"/>
        </index>
    </table>
</schema>
```

After modifying, regenerate the whitelist:
```bash
php bin/magento setup:db-declaration:generate-whitelist --module-name=Vendor_Module
```

### Collection Usage
```php
$collection = $this->collectionFactory->create();
$collection->addFieldToFilter('is_active', true)
    ->addFieldToFilter('created_at', ['gteq' => $date])
    ->setPageSize(50)
    ->setCurPage(1)
    ->setOrder('created_at', 'DESC');
```

**Avoid N+1 queries**: Use `addFieldToSelect()` and join tables when possible rather than loading related entities inside loops.

---

## Testing

### Test Types and Locations
```
Test/
├── Unit/              # Fast, isolated, mock all dependencies
├── Integration/       # Requires Magento framework bootstrap
└── Mftf/              # End-to-end browser tests
    └── Test/
```

### Running Tests
```bash
# Unit tests for a specific module
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/Vendor/Module/Test/Unit/

# Integration tests (requires test database)
cd dev/tests/integration
../../../vendor/bin/phpunit -c phpunit.xml.dist --testsuite "Magento Integration Tests"

# Static analysis
vendor/bin/phpcs --standard=Magento2 app/code/Vendor/Module

# Magento's built-in static tests
php vendor/bin/phpunit -c dev/tests/static/phpunit.xml.dist
```

### Unit Test Example
```php
<?php
declare(strict_types=1);

namespace Vendor\Module\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Vendor\Module\Model\Calculator;

class CalculatorTest extends TestCase
{
    private Calculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new Calculator();
    }

    public function testCalculateDiscount(): void
    {
        $result = $this->calculator->calculateDiscount(100.0, 10);
        $this->assertEquals(90.0, $result);
    }
}
```

---

## Common CLI Commands (Development)

```bash
# Cache management
php bin/magento cache:flush                          # Flush all caches
php bin/magento cache:clean config layout block_html # Clean specific caches

# Module management
php bin/magento module:enable Vendor_Module
php bin/magento module:disable Vendor_Module
php bin/magento module:status                        # List module status

# Setup
php bin/magento setup:upgrade                        # Run setup scripts
php bin/magento setup:di:compile                     # Generate DI config
php bin/magento setup:static-content:deploy -f       # Deploy static content (-f for dev mode)

# Indexing
php bin/magento indexer:reindex                      # Reindex all
php bin/magento indexer:status                        # Check indexer status

# Developer mode
php bin/magento deploy:mode:show
php bin/magento deploy:mode:set developer

# Code generation cleanup
rm -rf generated/code/* generated/metadata/*
php bin/magento setup:di:compile
```

---

## Anti-Patterns to Avoid

1. **Never use `ObjectManager` directly** — always use constructor DI.
2. **Never modify core files** — use plugins, observers, or preferences.
3. **Never use raw SQL queries** — use resource models, collections, or search criteria.
4. **Never put business logic in controllers** — controllers should delegate to service classes.
5. **Never put business logic in templates** — use ViewModels or Block methods.
6. **Never use `exit()` or `die()`** — throw exceptions and let Magento handle responses.
7. **Never hardcode store-specific values** — use system configuration.
8. **Never skip type hints** — always use strict typing.
9. **Never ignore Magento's exception hierarchy** — use framework exceptions like `NoSuchEntityException`, `CouldNotSaveException`, etc.
10. **Never create god classes** — keep classes focused and under 300 lines.
