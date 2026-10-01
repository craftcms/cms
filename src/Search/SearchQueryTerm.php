<?php

declare(strict_types=1);

namespace CraftCms\Cms\Search;

/**
 * @since 6.0.0
 */
class SearchQueryTerm
{
    public ?bool $subLeft = null;

    public ?bool $subRight = null;

    public ?bool $exclude = null;

    public ?bool $exact = null;

    public ?string $attribute = null;

    public ?string $term = null;

    public bool $phrase = false;
}
