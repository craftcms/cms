<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console;

use Illuminate\Console\Application as ConsoleApplication;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 */
final class Kernel extends \Illuminate\Foundation\Console\Kernel
{
    /** {@inheritdoc} */
    #[\Override]
    protected function getArtisan(): ConsoleApplication
    {
        if (is_null($this->artisan)) {
            $this->artisan = new Application($this->app, $this->events, '')
                ->resolveCommands($this->commands)
                ->setContainerCommandLoader();

            if ($this->symfonyDispatcher instanceof EventDispatcher) {
                $this->artisan->setDispatcher($this->symfonyDispatcher);
                $this->artisan->setSignalsToDispatchEvent();
            }
        }

        return $this->artisan;
    }

    #[\Override]
    protected function shouldDiscoverCommands(): bool
    {
        return true;
    }
}
