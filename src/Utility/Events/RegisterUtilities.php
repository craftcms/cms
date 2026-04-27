<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Events;

use CraftCms\Cms\Utility\Utility;
use Illuminate\Support\Collection;

/**
 * @event RegisterUtilities The event that is triggered when registering utilities.
 *
 * Utilities must extend {@see Utility}.
 *
 * Read more about creating utilities in the [documentation](https://craftcms.com/docs/5.x/extend/utilities.html).
 * ---
 * ```php
 * use Illuminate\Support\Facades\Event;
 * use CraftCms\Cms\Utility\Events\RegisterUtilities;
 *
 * Event::listen(RegisterUtilities::class, function(RegisterUtilities $event) {
 *     $event->types[] = MyUtilityType::class;
 * });
 * ```
 */
class RegisterUtilities
{
    public function __construct(
        public Collection $types,
    ) {}
}
