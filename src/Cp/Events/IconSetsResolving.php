<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Events;

use CraftCms\Cms\Cp\IconSets;
use CraftCms\Cms\Cp\IconSets\IconSet;

/**
 * @event IconSetsResolving The event that is triggered when registering custom icon sets.
 *
 * ```php
 * use CraftCms\Cms\Cp\Events\IconSetsResolving;
 * use Illuminate\Support\Facades\Event;
 *
 * Event::listen(function(IconSetsResolving $event) {
 *     $event->iconSets[] = new MyIconSet('my-icons', 'My Icons');
 * });
 * ```
 *
 * @see IconSets::getIconSets()
 * @since 5.12.0
 */
class IconSetsResolving
{
    public function __construct(
        /** @var list<IconSet> The registered custom icon sets */
        public array $iconSets = [],
    ) {}
}
