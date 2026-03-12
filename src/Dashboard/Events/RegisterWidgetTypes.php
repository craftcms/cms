<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Events;

use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Dashboard\Widgets\Widget;
use Illuminate\Support\Collection;

/**
 * @event RegisterWidgetTypes The event that is triggered when registering Dashboard widget types.
 *
 * Dashboard widgets must implement {@see WidgetInterface}. {@see Widget} provides a base implementation.
 *
 * See [Widget Types](https://craftcms.com/docs/5.x/extend/widget-types.html) for documentation on creating Dashboard widgets.
 * ---
 * ```php
 * use CraftCms\Cms\Dashboard\Events\RegisterWidgetTypes;
 *
 * Event::listen(RegisterWidgetTypes::class, function(RegisterWidgetTypes $event) {
 *     $event->types->add(MyWidgetType::class);
 * });
 * ```
 */
class RegisterWidgetTypes
{
    public function __construct(
        /** @var Collection<int, class-string<WidgetInterface>> */
        public Collection $types,
    ) {}
}
