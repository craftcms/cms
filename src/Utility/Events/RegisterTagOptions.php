<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Events;

use CraftCms\Cms\Utility\Utilities\ClearCaches;

/**
 * @event RegisterTagOptions The event that is triggered when registering cache tag invalidation options.
 *
 * Each option added to {@see RegisterTagOptions::$options} should be an array that has the following keys:
 *
 * - `tag` – The cache tag name that should be cleared.
 * - `label` – A human-facing label for the cache tag option.
 *
 * @see ClearCaches::tagOptions()
 */
class RegisterTagOptions
{
    public function __construct(
        /** @var array List of registered cache options for the Clear Caches tool. */
        public array $options,
    ) {}
}
