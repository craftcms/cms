<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig\Events;

/**
 * @event ConfigEvent The event that is triggered when an item is added to the config.
 *
 * ---
 *
 * ```php
 * use CraftCms\Cms\ProjectConfig\Events\ItemAdded;
 * use CraftCms\Cms\ProjectConfig\ProjectConfig;
 *
 * Event::listen(ItemAdded::class, function(ItemAdded $e) {
 *     // Ensure the item is also added in the database...
 * });
 * ```
 */
final class ItemAdded extends ConfigEvent {}
