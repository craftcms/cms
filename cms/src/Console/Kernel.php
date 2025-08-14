<?php

namespace CraftCms\Cms\Console;

use Illuminate\Console\Application as ConsoleApplication;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @since 6.0.0
 *
 * @internal
 */
final class Kernel extends \Illuminate\Foundation\Console\Kernel
{
    protected function getArtisan(): ConsoleApplication
    {
        if (is_null($this->artisan)) {
            $this->artisan = (new Application($this->app, $this->events, ''))
                ->resolveCommands($this->commands)
                ->setContainerCommandLoader();

            $this->artisan->setName('Craft CMS');

            if ($this->symfonyDispatcher instanceof EventDispatcher) {
                $this->artisan->setDispatcher($this->symfonyDispatcher);
                $this->artisan->setSignalsToDispatchEvent();
            }
        }

        return $this->artisan;
    }

    protected function shouldDiscoverCommands(): bool
    {
        return get_class($this) === self::class;
    }
}
