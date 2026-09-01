<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig\Events;

/**
 * @event ProjectConfigRebuilt The event that is triggered when the project config is being rebuilt.
 *
 * ---
 *
 * ```php
 * use CraftCms\Cms\ProjectConfig\Events\ProjectConfigRebuilt;
 * use CraftCms\Cms\ProjectConfig\ProjectConfig;
 *
 * Event::listen(ProjectConfigRebuilt::class, function(ProjectConfigRebuilt $e) {
 *     // Add plugin’s project config data...
 *    $e->config['myPlugin']['key'] = $value;
 * });
 * ```
 */
class ProjectConfigRebuilt
{
    /** @param array<string, mixed> $config */
    public function __construct(
        public array $config,
    ) {}
}
