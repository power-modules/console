<?php

/**
 * This file is part of the Modular Framework package.
 *
 * (c) 2025 Evgenii Teterin
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Modular\Console\PowerModule\Setup;

use Modular\Console\Contract\ProvidesConsoleCommands;
use Modular\Framework\Container\InstanceResolver\InstanceViaContainerResolver;
use Modular\Framework\PowerModule\Contract\PowerModuleSetup;
use Modular\Framework\PowerModule\Setup\PowerModuleSetupDto;
use Modular\Framework\PowerModule\Setup\SetupPhase;
use ReflectionClass;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;

final class ConsoleCommandsSetup implements PowerModuleSetup
{
    private Application $console;
    private bool $applicationRegistered = false;

    /** @var array<string,class-string<Command>> */
    private array $commandMap = [];

    public function __construct()
    {
        $this->console = new Application();
    }

    public function setup(PowerModuleSetupDto $powerModuleSetupDto): void
    {
        if ($powerModuleSetupDto->setupPhase === SetupPhase::Pre) {
            $this->collectCommands($powerModuleSetupDto);
            return;
        }

        $this->registerApplication($powerModuleSetupDto);
        $this->bridgeCommandsToRootContainer($powerModuleSetupDto);
    }

    private function collectCommands(PowerModuleSetupDto $powerModuleSetupDto): void
    {
        if (!$powerModuleSetupDto->powerModule instanceof ProvidesConsoleCommands) {
            return;
        }

        foreach ($powerModuleSetupDto->powerModule->getConsoleCommands() as $commandClass) {
            $attributes = (new ReflectionClass($commandClass))->getAttributes(AsCommand::class);
            if ($attributes !== []) {
                $this->commandMap[$attributes[0]->newInstance()->name] = $commandClass;
            }
        }
    }

    private function registerApplication(PowerModuleSetupDto $powerModuleSetupDto): void
    {
        if ($this->applicationRegistered) {
            return;
        }

        $this->applicationRegistered = true;

        $commandLoader = new ContainerCommandLoader(
            $powerModuleSetupDto->rootContainer,
            $this->commandMap,
        );

        if ($powerModuleSetupDto->rootContainer->has(Application::class)) {
            $console = $powerModuleSetupDto->rootContainer->get(Application::class);
        } else {
            $powerModuleSetupDto->rootContainer->set(Application::class, $this->console);
            $console = $this->console;
        }

        $console->setCommandLoader($commandLoader);
    }

    private function bridgeCommandsToRootContainer(PowerModuleSetupDto $powerModuleSetupDto): void
    {
        if (!$powerModuleSetupDto->powerModule instanceof ProvidesConsoleCommands) {
            return;
        }

        foreach ($powerModuleSetupDto->powerModule->getConsoleCommands() as $commandClass) {
            $powerModuleSetupDto->rootContainer->set(
                $commandClass,
                $powerModuleSetupDto->moduleContainer,
                InstanceViaContainerResolver::class,
            );
        }
    }
}
