<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Events;

use CraftCms\Cms\Utility\Utilities\ClearCaches;

/**
 * @event RegisterCacheOptions The event that is triggered when registering cache options.
 *
 * Each option added to {@see RegisterCacheOptions::$options} should be an array that has the following keys:
 *
 * - `key` – An identifying key for the cache option.
 * - `label` – A human-facing label for the cache option.
 * - `action` – Either the path to a folder that should be cleared, or a callable that should handle the cache clearing.
 * - `info` _(optional)_ – A description of the cache option.
 *
 * @see ClearCaches::cacheOptions()
 */
final class RegisterCacheOptions
{
    public function __construct(
        /**
         * @var array List of registered cache options for the Clear Caches tool.
         */
        public array $options
    ) {}
}
