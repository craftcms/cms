<?php

namespace CraftCms\Cms\ProjectConfig\Events;

/**
 * @event RebuildConfig The event that is triggered when the project config is being rebuilt.
 *
 * ---
 *
 * ```php
 * use CraftCms\Cms\ProjectConfig\Events\RebuildConfig;
 * use CraftCms\Cms\ProjectConfig\ProjectConfig;
 *
 * Event::listen(RebuildConfig::class, function(RebuildConfig $e) {
 *     // Add plugin’s project config data...
 *    $e->config['myPlugin']['key'] = $value;
 * });
 * ```
 *
 * @since 6.0.0
 */
final class RebuildConfig
{
    public function __construct(
        public array $config,
    ) {}
}
