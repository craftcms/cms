<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\TableCells;

/**
 * @since 6.0.0
 */
readonly class TableCellContext
{
    /** @param string|list<string> $path */
    public function __construct(
        public string|array $path,
        public mixed $value = null,
        public ?string $locale = null,
    ) {}
}
