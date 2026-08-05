<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use Illuminate\Support\Collection;

/**
 * @event ElementSourceSortOptionsResolving The event that is triggered when defining the available sort options for a source.
 */
class ElementSourceSortOptionsResolving
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
         * @var Collection<string, array<string, mixed>> The sort option definitions.
         *
         * Each sort option should be defined by an array with the following keys:
         *
         * - `label` – The sort option label
         * - `orderBy` – An array or comma-delimited string of columns to order the query by
         * - `attribute` _(optional)_ – The table attribute name that this option is associated with
         *   (required if `orderBy` is an array or more than one column name)
         */
        public Collection $sortOptions,
    ) {}
}
