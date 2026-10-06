<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Data;

/**
 * @since 6.0.0
 */
readonly class FieldMergeResult
{
    public function __construct(
        public int $updatedLayouts,
        public string $migrationPath,
    ) {}
}
