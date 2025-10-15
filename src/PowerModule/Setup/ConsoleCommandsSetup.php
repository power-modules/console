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

use Modular\Framework\PowerModule\Contract\ExportsComponents;
use Modular\Framework\PowerModule\Contract\PowerModuleSetup;
use Modular\Framework\PowerModule\Setup\PowerModuleSetupDto;
use Modular\Framework\PowerModule\Setup\SetupPhase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;

final class ConsoleCommandsSetup implements PowerModuleSetup
{
    private Application $console;
    private ?CommandLoaderInterface $commandLoader = null;

    /**
     * @var array<string,class-string<Command>> $commandMap
     */
    private array $commandMap = [];

    public function __construct()
    {
        $this->console = new Application();
    }

    public function setup(PowerModuleSetupDto $powerModuleSetupDto): void
    {
        if (!$powerModuleSetupDto->powerModule instanceof ExportsComponents) {
            return;
        }

        if ($powerModuleSetupDto->setupPhase === SetupPhase::Pre) {
            // During PRE phase we just collect all commands to be registered later
            foreach ($powerModuleSetupDto->powerModule::exports() as $component) {
                if (is_subclass_of($component, Command::class)) {
                    if ($attribute = (new ReflectionClass($component))->getAttributes(AsCommand::class)) {
                        $this->commandMap[$attribute[0]->newInstance()->name] = $component;
                    }
                }
            }

            return;
        }

        if ($this->commandLoader !== null) {
            return;
        }

        $this->commandLoader = new ContainerCommandLoader(
            $powerModuleSetupDto->rootContainer,
            $this->commandMap,
        );

        $this->console->setCommandLoader($this->commandLoader);
        $powerModuleSetupDto->rootContainer->set(Application::class, $this->console);
    }
}
