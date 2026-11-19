<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Events;

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Ui\Contracts\Node;

/**
 * @event EntryMetaFieldsResolving The event that is triggered when defining the meta fields.
 *
 * The editor sidebar's fields are {@see Node}s keyed `type`, `slug`, `parent`,
 * `authors`, `postDate`, and `expiryDate`, so removing a key hides that field.
 * Values that aren't Nodes are ignored. The deprecated
 * {@see Entry::metaFieldsHtml()} dispatches it with HTML strings under the
 * same keys.
 *
 * @see Entry::metaFieldsNodes()
 * @since 6.0.0
 */
class EntryMetaFieldsResolving
{
    public function __construct(
        public Entry $entry,
        public bool $static,
        /** @var array<string|int, string|Node|null> */
        public array $fields,
    ) {}
}
