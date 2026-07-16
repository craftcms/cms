<?php

declare(strict_types=1);

namespace CraftCms\Cms\Translation;

use Illuminate\Contracts\Events\Dispatcher as LaravelDispatcher;
use Psr\EventDispatcher\EventDispatcherInterface;

readonly class LaravelEventDispatcher implements EventDispatcherInterface
{
    public function __construct(
        private LaravelDispatcher $events,
    ) {}

    #[\Override]
    public function dispatch(object $event)
    {
        $this->events->dispatch($event);

        return $event;
    }
}
