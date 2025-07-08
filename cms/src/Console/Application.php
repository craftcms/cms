<?php

namespace Craft\Cms\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class Application extends \Illuminate\Console\Application
{
    public function resolve($command): ?SymfonyCommand
    {
        if (! $command instanceof Command) {
            $command = $this->laravel->make($command);
        }

        if (! in_array(CraftCommand::class, class_uses($command))) {
            $command->setHidden();
        } else {
            /** @var CraftCommand $command */
            $command->removeCraftGroup();
        }

        return $this->add($command);
    }
}
