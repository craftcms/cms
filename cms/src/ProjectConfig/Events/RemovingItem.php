<?php

namespace CraftCms\Cms\ProjectConfig\Events;

/**
 * @event ConfigEvent The event that is triggered when an item is being removed from the config.
 *
 * ---
 *
 * ```php
 * use CraftCms\Cms\ProjectConfig\Events\RemovingItem;
 * use CraftCms\Cms\ProjectConfig\ProjectConfig;
 *
 * Event::listen(RemovingItem::class, function(RemovingItem $e) {
 *     // Ensure the item is also removed in the database...
 * });
 * ```
 *
 * @since 6.0.0
 */
final class RemovingItem extends ConfigEvent {}
