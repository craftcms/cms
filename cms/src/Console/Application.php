<?php

namespace CraftCms\Cms\Console;

use CraftCms\Yii2Adapter\Console\LegacyCraftCommand;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

/**
 * @since 6.0.0
 *
 * @internal
 */
final class Application extends \Illuminate\Console\Application
{
    public function resolve($command): ?SymfonyCommand
    {
        if (! $command instanceof Command) {
            $command = $this->laravel->make($command);
        }

        if (! in_array(CraftCommand::class, class_uses($command))) {
            $command->setHidden();
        } else {
            /** @var LegacyCraftCommand $command */
            $command->removeCraftGroup();
        }

        return $this->add($command);
    }
}
