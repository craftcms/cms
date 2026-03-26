<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig\Events;

/**
 * @event ConfigEvent The event that is triggered when an item is updated in the config.
 *
 * ---
 *
 * ```php
 * use CraftCms\Cms\ProjectConfig\Events\ItemUpdated;
 * use CraftCms\Cms\ProjectConfig\ProjectConfig;
 *
 * Event::listen(ItemUpdated::class, function(ItemUpdated $e) {
 *     // Ensure the item is also updated in the database...
 * });
 * ```
 */
class ItemUpdated extends ConfigEvent {}
