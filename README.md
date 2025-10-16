# Console Plugin

[![Packagist Version](https://img.shields.io/packagist/v/power-modules/console)](https://packagist.org/packages/power-modules/console)
[![PHP Version](https://img.shields.io/packagist/php-v/power-modules/console)](https://packagist.org/packages/power-modules/console)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-blue)](#)

A **PowerModuleSetup extension** for the [Power Modules Framework](https://github.com/power-modules/framework) that automatically discovers and registers Symfony Console commands from your modules.

## 🎯 Purpose

This plugin bridges Symfony Console with the Power Modules Framework's modular architecture. It allows modules to export console commands while maintaining the framework's encapsulation principles. Commands are auto-discovered, lazily loaded from the DI container, and registered into a central `Console\Application`.

## ✨ Key Features

- **🔍 Auto-Discovery**: Automatically finds commands exported by modules via `#[AsCommand]` attribute
- **⚡ Lazy Loading**: Commands are instantiated only when executed via `ContainerCommandLoader`
- **🔒 Encapsulation**: Each module's commands remain isolated in their module's container scope
- **💉 DI Integration**: Full dependency injection support for command constructors
- **📦 Zero Configuration**: Just export your commands from modules - registration is automatic

## 📦 Installation

```bash
composer require power-modules/console
```

## 🚀 Quick Start

**1. Create a command in your module:**

```php
<?php

namespace MyApp\Orders;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'orders:process', description: 'Process pending orders')]
final class ProcessOrdersCommand extends Command
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->orderService->processPendingOrders();
        $output->writeln('Orders processed successfully!');
        
        return Command::SUCCESS;
    }
}
```

**2. Export the command from your module:**

```php
<?php

namespace MyApp\Orders;

use Modular\Framework\PowerModule\Contract\ExportsComponents;
use Modular\Framework\PowerModule\Contract\PowerModule;
use Modular\Framework\PowerModule\Contract\ConfigurableContainerInterface;

final class OrderModule implements PowerModule, ExportsComponents
{
    public static function exports(): array
    {
        return [
            ProcessOrdersCommand::class, // Export your command
        ];
    }

    public function register(ConfigurableContainerInterface $container): void
    {
        $container->set(OrderService::class, OrderService::class); // Stays private to this module
        
        $container->set(ProcessOrdersCommand::class, ProcessOrdersCommand::class)
            ->addArguments([OrderService::class]) // DI just works!
        ;
    }
}
```

**3. Add the setup to your application:**

```php
# bin/console
<?php

declare(strict_types=1);

use Modular\Console\PowerModule\Setup\ConsoleCommandsSetup;
use Modular\Framework\App\ModularAppBuilder;
use Symfony\Component\Console\Application;

require __DIR__ . '/../vendor/autoload.php';

$app = new ModularAppBuilder(__DIR__)
    ->withModules(
        \MyApp\Orders\OrderModule::class,
        \MyApp\Users\UserModule::class,
    )
    ->addPowerModuleSetup(new ConsoleCommandsSetup())
    ->build()
;

// Get the configured Console Application
$console = $app->get(Application::class);
$console->run();
```

**4. Run your commands:**

```bash
php bin/console orders:process
php bin/console list  # See all registered commands
```

## 🔧 How It Works

The plugin uses a **two-phase setup process** aligned with the framework's PowerModuleSetup lifecycle:

1. **PRE Phase**: Scans all modules implementing `ExportsComponents`, discovers classes extending `Command` with `#[AsCommand]` attributes, and builds a command map
2. **POST Phase**: Creates a `ContainerCommandLoader` with the collected commands and registers the `Console\Application` in the root container

Commands are **lazily loaded** - they're instantiated from the container only when executed, ensuring efficient memory usage and fast startup.

## 📋 Requirements

- **PHP**: ^8.4
- **power-modules/framework**: ^2.1
- **symfony/console**: ^7.3

## 🏗️ Architecture

- Commands must extend `Symfony\Component\Console\Command\Command`
- Commands must be annotated with `#[AsCommand]` attribute
- Commands must be in the module's `exports()` array
- Dependencies are resolved via constructor injection from the module's container
- The `Console\Application` instance is registered in the root container for retrieval

## 🤝 Contributing

Contributions are welcome! This project follows the same standards as the Power Modules Framework:

- **PHPStan Level 8** - Very strict static analysis
- **PHP CS Fixer** - Code style consistency
- **PHPUnit** - Comprehensive test coverage

```bash
make test       # Run tests
make phpstan    # Static analysis
make codestyle  # Code style check
```

## 📄 License

MIT License. See [LICENSE](LICENSE) for details.

---

**Part of the [Power Modules Framework](https://github.com/power-modules/framework) ecosystem** - Building truly modular PHP applications with runtime-enforced encapsulation.
