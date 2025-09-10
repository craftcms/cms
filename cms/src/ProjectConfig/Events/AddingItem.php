<?php

namespace CraftCms\Cms\ProjectConfig\Events;

/**
 * @event ConfigEvent The event that is triggered when an item is being added to the config.
 *
 * ---
 *
 * ```php
 * use CraftCms\Cms\ProjectConfig\Events\AddingItem;
 * use CraftCms\Cms\ProjectConfig\ProjectConfig;
 *
 * Event::listen(AddingItem::class, function(AddingItem $e) {
 *     // Ensure the item is also added in the database...
 * });
 * ```
 *
 * @since 6.0.0
 */
final class AddingItem extends ConfigEvent {}
