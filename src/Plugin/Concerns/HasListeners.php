<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\Support\Arr;
use Illuminate\Support\Facades\Event;

/**
 * @mixin Plugin
 *
 * @internal
 */
trait HasListeners
{
    /**
     * Array of event class => Listener class.
     *
     * @var array<class-string, class-string|class-string[]>
     */
    protected array $events = [];

    public function bootHasListeners(): void
    {
        foreach ($this->events as $event => $listeners) {
            foreach (Arr::wrap($listeners) as $listener) {
                Event::listen($event, $listener);
            }
        }
    }
}
