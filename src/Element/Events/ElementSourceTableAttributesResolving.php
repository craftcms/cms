<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use Illuminate\Support\Collection;

/**
 * @event ElementSourceTableAttributesResolving The event that is triggered when defining the available table attributes for a source.
 */
class ElementSourceTableAttributesResolving
{
    public function __construct(
        /**
         * @var class-string<ElementInterface> The element type class
         */
        public string $elementType,

        /**
         * @var string The element source key
         */
        public string $source,

        /**
         * @var Collection<string, array<string, mixed>> The available columns that can be shown.
         *
         * This should be set to an array whose keys represent element attribute names, and whose values are
         * nested arrays with the following keys:
         *
         * - `label` – The table column header
         * - `icon` _(optional)_ – The name of the icon that should be shown instead of a textual label (e.g. `'world'`)
         *
         * The first item in the array will determine the first table column’s header (and which
         * [[\CraftCms\Cms\Element\Contracts\ElementInterface::sortOptions()|sort option]] it should be mapped to, if any), however it
         * doesn’t have any effect on the table body, because the first column is reserved for displaying whatever
         * the elements’ [[\CraftCms\Cms\Element\Contracts\ElementInterface::getUiLabel()|getUiLabel()]] methods return.
         */
        public Collection $attributes,
    ) {}
}
