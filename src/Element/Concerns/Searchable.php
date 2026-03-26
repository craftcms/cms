<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Events\DefineKeywords;
use CraftCms\Cms\Support\Str;

/**
 * Searchable provides search keyword functionality for elements.
 *
 * This trait contains methods for generating and retrieving search keywords from element attributes,
 * as well as events for customizing search keyword behavior and registering searchable attributes.
 *
 * @mixin Element
 *
 * @internal
 */
trait Searchable
{
    /**
     * @var int|null The element's search score, if the [[\craft\elements\db\ElementQuery::search]] parameter was used when querying for the element
     */
    public ?int $searchScore = null;

    /**
     * @var bool Whether the element's search keywords should be indexed immediately.
     *
     * If `null`, the search index will only be updated immediately for console requests.
     *
     * @since 5.8.0
     */
    public ?bool $updateSearchIndexImmediately = null;

    /**
     * Returns the search keywords for a given search attribute.
     */
    public function getSearchKeywords(string $attribute): string
    {
        event($event = new DefineKeywords(
            element: $this,
            attribute: $attribute,
        ));

        if ($event->handled) {
            return $event->keywords;
        }

        return $this->searchKeywords($attribute);
    }

    /**
     * Returns the search keywords for a given search attribute.
     *
     * @since 3.5.0
     */
    protected function searchKeywords(string $attribute): string
    {
        return Str::toString($this->$attribute);
    }
}
