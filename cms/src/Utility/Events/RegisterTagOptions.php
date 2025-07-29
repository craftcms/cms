<?php

namespace CraftCms\Cms\Utility\Events;

/**
 * @event RegisterTagOptions The event that is triggered when registering cache tag invalidation options.
 *
 * Each option added to [[RegisterTagOptions::$options]] should be an array that has the following keys:
 *
 * - `tag` – The cache tag name that should be cleared.
 * - `label` – A human-facing label for the cache tag option.
 *
 * @see \CraftCms\Cms\Utility\Utilities\ClearCaches::tagOptions()
 * @since 6.0.0
 */
class RegisterTagOptions
{
    public function __construct(
        /** @var array List of registered cache options for the Clear Caches tool. */
        public array $options,
    ) {}
}
