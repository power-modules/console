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

namespace Modular\Console\Test\PowerModule;

use Modular\Console\PowerModule\Setup\ConsoleCommandsSetup;
use Modular\Console\Test\PowerModule\Stub\ModuleWithCommands;
use Modular\Console\Test\PowerModule\Stub\ModuleWithoutCommands;
use Modular\Console\Test\PowerModule\Stub\NotACommand;
use Modular\Framework\App\Config\Config;
use Modular\Framework\App\Config\Setting;
use Modular\Framework\App\ModularAppBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;

final class ConsoleCommandsSetupTest extends TestCase
{
    public function testAppHasConsoleApplication(): void
    {
        $app = new ModularAppBuilder(__DIR__)
            ->withConfig(Config::forAppRoot(__DIR__)->set(Setting::CachePath, sys_get_temp_dir()))
            ->withPowerSetup(new ConsoleCommandsSetup())
            ->build()
        ;

        self::assertTrue($app->has(Application::class));
        self::assertInstanceOf(Application::class, $app->get(Application::class));
    }

    public function testConsoleApplicationDoesNotHaveCommandsWhenModuleExportsNoCommands(): void
    {
        $app = new ModularAppBuilder(__DIR__)
            ->withConfig(Config::forAppRoot(__DIR__)->set(Setting::CachePath, sys_get_temp_dir()))
            ->withPowerSetup(new ConsoleCommandsSetup())
            ->withModules(
                ModuleWithoutCommands::class,
            )
            ->build()
        ;

        $console = $app->get(Application::class);

        self::assertFalse($console->has('a-command'));
        self::assertFalse($console->has('b-command'));
        self::assertTrue($app->has(NotACommand::class));
    }

    public function testConsoleApplicationHasCommandsWhenModuleExportsCommands(): void
    {
        $app = new ModularAppBuilder(__DIR__)
            ->withConfig(Config::forAppRoot(__DIR__)->set(Setting::CachePath, sys_get_temp_dir()))
            ->withPowerSetup(new ConsoleCommandsSetup())
            ->withModules(
                ModuleWithCommands::class,
            )
            ->build()
        ;

        $console = $app->get(Application::class);

        self::assertTrue($console->has('a-command'));
        self::assertTrue($console->has('b-command'));

        $bCommand = $console->get('a-command');
        $input = new \Symfony\Component\Console\Input\ArrayInput([]);
        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        $bCommand->run($input, $output);
        self::assertSame('ACommand executed', trim($output->fetch()));

        $bCommand = $console->get('b-command');
        $input = new \Symfony\Component\Console\Input\ArrayInput([]);
        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        $bCommand->run($input, $output);
        self::assertSame('BCommand executed with injected value', trim($output->fetch()));
    }
}
