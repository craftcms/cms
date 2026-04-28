<?php

declare(strict_types=1);

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
 */
class AddingItem extends ConfigEvent {}
