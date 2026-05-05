<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig\Events;

/**
 * @event ConfigEvent The event that is triggered when an item is being updated in the config.
 *
 * ---
 *
 * ```php
 * use CraftCms\Cms\ProjectConfig\Events\ProjectConfigItemUpdated;
 * use CraftCms\Cms\ProjectConfig\ProjectConfig;
 *
 * Event::listen(ProjectConfigItemUpdated::class, function(ProjectConfigItemUpdated $e) {
 *     // Ensure the item is also updated in the database...
 * });
 * ```
 */
class ProjectConfigItemUpdated extends ConfigEvent {}
