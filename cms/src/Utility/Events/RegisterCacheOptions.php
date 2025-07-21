<?php

namespace Craft\Cms\Utility\Events;

/**
 * @event RegisterCacheOptions The event that is triggered when registering cache options.
 *
 * Each option added to [[RegisterCacheOptions::$options]] should be an array that has the following keys:
 *
 * - `key` – An identifying key for the cache option.
 * - `label` – A human-facing label for the cache option.
 * - `action` – Either the path to a folder that should be cleared, or a callable that should handle the cache clearing.
 * - `info` _(optional)_ – A description of the cache option.
 *
 * @see \Craft\Cms\Utility\Utilities\ClearCaches::cacheOptions()
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 6.0.0
 */
class RegisterCacheOptions
{
    public function __construct(
        /**
         * @var array List of registered cache options for the Clear Caches tool.
         */
        public array $options
    ) {}
}
