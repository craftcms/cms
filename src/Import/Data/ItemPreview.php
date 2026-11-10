<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Data;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Import\Enums\ItemPreviewResult;

/**
 * What importing an item would do. Nothing is saved to find out.
 *
 * @since 6.0.0
 */
readonly class ItemPreview
{
    /**
     * @param  ElementInterface|null  $element  The element the item would create or update, with its values set; `null` when it's skipped
     *                                          before one was found, or couldn't be read.
     * @param  array<string, mixed>  $matchCriteria  The values the item was matched on, by destination handle.
     * @param  list<string>  $errors
     */
    public function __construct(
        public ItemPreviewResult $result,
        public ?ElementInterface $element,
        public array $matchCriteria = [],
        public array $errors = [],
    ) {}
}
