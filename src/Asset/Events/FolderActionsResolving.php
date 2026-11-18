<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Element\ElementSources;

/**
 * @event FolderActionsResolving The event that is triggered when defining the buttons for the folder on screen.
 *
 * On the assets index ({@see ElementSources::CONTEXT_INDEX}), they’re items in
 * a menu beside “Upload files”, which only shows when there are any. In asset selector modals
 * ({@see ElementSources::CONTEXT_MODAL}), they’re buttons beside “Upload files”.
 * Each is a server-driven action item, so one that needs client-side behavior
 * should have an `event` action that the plugin’s own script listens for:
 *
 * ```php
 * use CraftCms\Cms\Asset\Events\FolderActionsResolving;
 * use Illuminate\Support\Facades\Event;
 *
 * Event::listen(function(FolderActionsResolving $event) {
 *     $event->actions[] = [
 *         'label' => 'Generate image',
 *         'icon' => 'wand-magic-sparkles',
 *         'action' => [
 *             'type' => 'event',
 *             'name' => 'my-plugin:generate-image',
 *             'detail' => ['folderId' => $event->folder->id],
 *         ],
 *     ];
 * });
 * ```
 *
 * @since 6.0.0
 */
class FolderActionsResolving
{
    public function __construct(
        public VolumeFolder $folder,
        /** Where the buttons are shown: `index` or `modal`. */
        public string $context,
        /**
         * @var list<array<string, mixed>> The buttons, each with a `label` and an `action`. They can also have an
         *                                 `icon`, a `variant`, and `feedback`, as described in `docs/server-driven-menu-actions.md`.
         */
        public array $actions = [],
    ) {}
}
