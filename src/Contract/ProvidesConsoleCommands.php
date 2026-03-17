<?php

declare(strict_types=1);

namespace Modular\Console\Contract;

use Symfony\Component\Console\Command\Command;

interface ProvidesConsoleCommands
{
    /**
     * Get the console commands provided by this module.
     *
     * @return array<int,class-string<Command>>
     */
    public function getConsoleCommands(): array;
}
