<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig\Events;

/**
 * @event ConfigEvent The event that is triggered when an item is being updated in the config.
 *
 * ---
 *
 * ```php
 * use CraftCms\Cms\ProjectConfig\Events\UpdatingItem;
 * use CraftCms\Cms\ProjectConfig\ProjectConfig;
 *
 * Event::listen(UpdatingItem::class, function(UpdatingItem $e) {
 *     // Ensure the item is also updated in the database...
 * });
 * ```
 */
final class UpdatingItem extends ConfigEvent {}
