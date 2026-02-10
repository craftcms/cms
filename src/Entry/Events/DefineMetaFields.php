<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Events;

use CraftCms\Cms\Entry\Elements\Entry;

/**
 * @event DefineMetaFields The event that is triggered when defining the meta fields.
 *
 * @see Entry::metaFieldsHtml()
 */
final class DefineMetaFields
{
    public function __construct(
        public Entry $entry,
        public bool $static,
        /** @var string[] */
        public array $fields,
    ) {}
}
