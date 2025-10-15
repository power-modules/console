# Copilot Instructions for Power Modules Console Plugin

## Project Overview
This is a **Power Modules Console Plugin** - a library that integrates Symfony Console commands with the Power Modules framework's modular architecture. The plugin auto-discovers and registers console commands from modules using dependency injection and reflection.

## Key Architecture Patterns

### PowerModule Setup Pattern
- Main setup logic in `src/PowerModule/Setup/ConsoleCommandsSetup.php`
- Uses **two-phase setup**: PRE phase collects commands, POST phase registers them
- Commands are discovered via `ExportsComponents` interface and `#[AsCommand]` attributes
- Follow the pattern in test stubs: `ModuleWithCommands` exports command classes, `ModuleWithoutCommands` exports non-commands

### Console Command Registration
```php
// Commands must extend Symfony\Component\Console\Command\Command
// Use #[AsCommand] attribute for auto-discovery
#[AsCommand(name: 'command-name', description: 'Description')]
final class MyCommand extends Command
```

### Module Structure
- Modules implement `PowerModule` and `ExportsComponents` interfaces
- Export commands via static `exports()` method returning class-string array
- Register dependencies in `register(ConfigurableContainerInterface $container)` method
- See `test/PowerModule/Stub/ModuleWithCommands.php` for reference implementation

### Dependency Injection Pattern
```php
// In module's register() method:
$container->set(BCommand::class, BCommand::class)
    ->addArguments(['injected value']);

// In command constructor:
public function __construct(private string $dependency) {
    parent::__construct();
}
```

## Development Workflow

### Testing Commands
- Use `make test` to run PHPUnit tests
- Test pattern: Create app with `ModularAppBuilder`, add modules, verify console application
- Commands are lazily loaded via `ContainerCommandLoader`
- Example: `$console->has('command-name')` and `$console->get('command-name')`

### Code Quality
- `make codestyle` - PHP CS Fixer validation
- `make phpstan` - Static analysis (level 8, very strict)
- `make devcontainer` - Build dev container

### File Organization
- Source: `src/PowerModule/Setup/` - setup classes
- Tests: `test/PowerModule/` - unit tests with stub implementations
- Stubs: `test/PowerModule/Stub/` - example modules and commands for testing

## Critical Implementation Details

1. **Command Discovery**: Only classes extending `Command` with `#[AsCommand]` attribute are registered
2. **Container Integration**: Console Application is registered in DI container as `Application::class`
3. **Module Exports**: Commands must be in the module's `exports()` array to be discovered
4. **Two-Phase Setup**: PRE phase builds command map, POST phase creates CommandLoader
5. **Lazy Loading**: Commands are instantiated only when executed via `ContainerCommandLoader`

## Common Patterns
- All classes use `declare(strict_types=1)` and final classes
- PSR-4 autoloading: `Modular\Console\` namespace maps to `src/`
- Test namespace: `Modular\Console\Test\` maps to `test/`
- Constructor dependency injection with private readonly properties (PHP 8.4+)