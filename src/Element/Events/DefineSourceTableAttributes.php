<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;
use Illuminate\Support\Collection;

/**
 * @event DefineSourceTableAttributes The event that is triggered when defining the available table attributes for a source.
 */
class DefineSourceTableAttributes
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
         * @var Collection The available columns that can be shown.
         *
         * This should be set to an array whose keys represent element attribute names, and whose values are
         * nested arrays with the following keys:
         *
         * - `label` – The table column header
         * - `icon` _(optional)_ – The name of the icon that should be shown instead of a textual label (e.g. `'world'`)
         *
         * The first item in the array will determine the first table column’s header (and which
         * [[\craft\base\ElementInterface::sortOptions()|sort option]] it should be mapped to, if any), however it
         * doesn’t have any effect on the table body, because the first column is reserved for displaying whatever
         * the elements’ [[\craft\base\ElementInterface::getUiLabel()|getUiLabel()]] methods return.
         */
        public Collection $attributes,
    ) {}
}
