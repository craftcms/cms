<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Events;

use CraftCms\Cms\Entry\Elements\Entry;

/**
 * @event EntryMetaFieldsResolving The event that is triggered when defining the meta fields.
 *
 * @see Entry::metaFieldsHtml()
 * @since 6.0.0
 */
class EntryMetaFieldsResolving
{
    public function __construct(
        public Entry $entry,
        public bool $static,
        /** @var string[] */
        public array $fields,
    ) {}
}
