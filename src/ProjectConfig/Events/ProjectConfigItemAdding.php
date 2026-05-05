<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig\Events;

/**
 * @event ConfigEvent The event that is triggered when an item is being added to the config.
 *
 * ---
 *
 * ```php
 * use CraftCms\Cms\ProjectConfig\Events\ProjectConfigItemAdding;
 * use CraftCms\Cms\ProjectConfig\ProjectConfig;
 *
 * Event::listen(ProjectConfigItemAdding::class, function(ProjectConfigItemAdding $e) {
 *     // Ensure the item is also added in the database...
 * });
 * ```
 */
class ProjectConfigItemAdding extends ConfigEvent {}
