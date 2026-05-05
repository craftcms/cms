<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig\Events;

/**
 * @event ConfigEvent The event that is triggered when an item is being removed from the config.
 *
 * ---
 *
 * ```php
 * use CraftCms\Cms\ProjectConfig\Events\ProjectConfigItemRemoved;
 * use CraftCms\Cms\ProjectConfig\ProjectConfig;
 *
 * Event::listen(ProjectConfigItemRemoved::class, function(ProjectConfigItemRemoved $e) {
 *     // Ensure the item is also removed in the database...
 * });
 * ```
 */
class ProjectConfigItemRemoved extends ConfigEvent {}
