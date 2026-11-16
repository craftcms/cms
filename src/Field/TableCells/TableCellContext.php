<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\TableCells;

use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * @since 6.0.0
 */
readonly class TableCellContext
{
    /**
     * @param  string|list<string>  $path
     * @param  ElementInterface|null  $element  The element whose Table field is being edited, if any
     */
    public function __construct(
        public string|array $path,
        public mixed $value = null,
        public ?string $locale = null,
        public ?ElementInterface $element = null,
    ) {}
}
