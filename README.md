# Console

[![Packagist Version](https://img.shields.io/packagist/v/power-modules/console)](https://packagist.org/packages/power-modules/console)
[![PHP Version](https://img.shields.io/packagist/php-v/power-modules/console)](https://packagist.org/packages/power-modules/console)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-blue)](#)

A **PowerModuleSetup extension** for the [Power Modules Framework](https://github.com/power-modules/framework) that auto-discovers and registers Symfony Console commands from your modules.

## Why a separate interface?

The framework's `ExportsComponents` is for sharing services between modules via `ImportsComponentsSetup` — it belongs to inter-module dependency wiring. Console commands are not services another module imports; they are an outward-facing capability of a module. This package introduces `ProvidesConsoleCommands` to express that cleanly, without conflating command registration with cross-module service sharing.

## Installation

```bash
composer require power-modules/console
```

## Quick Start

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

**2. Implement `ProvidesConsoleCommands` in your module:**

```php
<?php

namespace MyApp\Orders;

use Modular\Console\Contract\ProvidesConsoleCommands;
use Modular\Framework\PowerModule\Contract\PowerModule;
use Modular\Framework\PowerModule\Contract\ConfigurableContainerInterface;

final class OrderModule implements PowerModule, ProvidesConsoleCommands
{
    public function getConsoleCommands(): array
    {
        return [
            ProcessOrdersCommand::class,
        ];
    }

    public function register(ConfigurableContainerInterface $container): void
    {
        $container->set(OrderService::class, OrderService::class);

        $container->set(ProcessOrdersCommand::class, ProcessOrdersCommand::class)
            ->addArguments([OrderService::class])
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
    ->withPowerSetup(new ConsoleCommandsSetup())
    ->build()
;

$app->get(Application::class)->run();
```

**4. Run your commands:**

```bash
php bin/console orders:process
php bin/console list
```

## How It Works

`ConsoleCommandsSetup` uses the framework's two-phase setup lifecycle:

**PRE phase** — for each module implementing `ProvidesConsoleCommands`, iterates `getConsoleCommands()` and reads the `#[AsCommand]` attribute on each class to build a name → class-string command map.

**POST phase** — two steps:

1. **Application registration** (once, on the first module): creates a `ContainerCommandLoader` from the collected command map and registers a `Console\Application` in the root container. If an `Application` is already present there (e.g. pre-registered by another module), it is reused rather than replaced.

2. **Command bridging** (per `ProvidesConsoleCommands` module): registers each command class in the root container, resolved from the module's own container. This keeps each command's DI wiring private to its module while making the command resolvable by the `ContainerCommandLoader`.

Commands are **lazily instantiated** — the container resolves a command only when it is actually executed.

## Requirements

- **PHP**: ^8.4
- **power-modules/framework**: ^2.1
- **symfony/console**: ^7.3

## Architecture

- Modules declare commands via `ProvidesConsoleCommands::getConsoleCommands()` — no need to also implement `ExportsComponents`
- Commands must extend `Symfony\Component\Console\Command\Command`
- Commands must carry the `#[AsCommand]` attribute (provides the command name)
- Dependencies are resolved from the module's own container via constructor injection
- The `Console\Application` instance is registered in the root container for retrieval

## Contributing

Contributions are welcome. This project follows the same standards as the Power Modules Framework:

- **PHPStan Level 8**
- **PHP CS Fixer**
- **PHPUnit**

```bash
make test       # Run tests
make phpstan    # Static analysis
make codestyle  # Code style check
```

## License

MIT License. See [LICENSE](LICENSE) for details.

---

**Part of the [Power Modules Framework](https://github.com/power-modules/framework) ecosystem.**

