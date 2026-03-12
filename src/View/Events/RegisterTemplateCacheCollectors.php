<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\Events;

use CraftCms\Cms\View\Contracts\CacheCollectorInterface;
use Illuminate\Support\Collection;

/**
 * @event RegisterTemplateCacheCollectors The event that is triggered when registering template cache collectors.
 *
 * CacheCollectors must implement {@see CacheCollectorInterface}.
 * ---
 * ```php
 * use CraftCms\Cms\View\Events\RegisterTemplateCacheCollectors;
 * use Illuminate\Support\Facades\Event;
 *
 * Event::listen(RegisterTemplateCacheCollectors::class, function(RegisterTemplateCacheCollectors $event) {
 *     $event->types->add(MyCollector::class);
 * });
 * ```
 */
class RegisterTemplateCacheCollectors
{
    /**
     * @param  Collection<class-string<CacheCollectorInterface>>  $types
     */
    public function __construct(
        public Collection $types,
    ) {}
}
