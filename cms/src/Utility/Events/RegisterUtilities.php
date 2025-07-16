<?php

namespace Craft\Cms\Utility\Events;

/**
 * @event RegisterUtilities The event that is triggered when registering utilities.
 *
 * Utilities must implement [[UtilityInterface]]. [[\craft\base\Utility]] provides a base implementation.
 *
 * Read more about creating utilities in the [documentation](https://craftcms.com/docs/5.x/extend/utilities.html).
 * ---
 * ```php
 * use Illuminate\Support\Facades\Event;
 * use Craft\Cms\Utility\Events\RegisterUtilities;
 *
 * Event::listen(RegisterUtilities::class, function(RegisterUtilities $event) {
 *     $event->types[] = MyUtilityType::class;
 * });
 * ```
 */
class RegisterUtilities
{
    public function __construct(
        public array $types
    ) {}
}
