<?php

namespace CraftCms\Cms\Console;

use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @property Application|null $artisan
 */
class Kernel extends \Illuminate\Foundation\Console\Kernel
{
    protected function getArtisan(): ?Application
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
        return get_class($this) === __CLASS__;
    }
}
