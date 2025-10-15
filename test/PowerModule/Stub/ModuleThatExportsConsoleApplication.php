<?php

declare(strict_types=1);

namespace Modular\Console\Test\PowerModule\Stub;

use Modular\Framework\Container\ConfigurableContainerInterface;
use Modular\Framework\PowerModule\Contract\ExportsComponents;
use Modular\Framework\PowerModule\Contract\PowerModule;
use Symfony\Component\Console\Application;

final class ModuleThatExportsConsoleApplication implements PowerModule, ExportsComponents
{
    public static function exports(): array
    {
        return [
            Application::class,
        ];
    }

    public function register(ConfigurableContainerInterface $container): void
    {
        $container->set(Application::class, Application::class)
            ->addArguments(['Pre-registered Console App'])
        ;
    }
}
