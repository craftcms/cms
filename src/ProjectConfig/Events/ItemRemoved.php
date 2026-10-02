<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig\Events;

/**
 * @event ConfigEvent The event that is triggered when an item is removed from the config.
 *
 * ---
 *
 * ```php
 * use CraftCms\Cms\ProjectConfig\Events\ItemRemoved;
 * use CraftCms\Cms\ProjectConfig\ProjectConfig;
 *
 * Event::listen(ItemRemoved::class, function(ItemRemoved $e) {
 *     // Ensure the item is also removed in the database...
 * });
 * ```
 *
 * @since 6.0.0
 */
class ItemRemoved extends ConfigEvent {}
