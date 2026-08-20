<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Data;

readonly class FieldMergeResult
{
    public function __construct(
        public int $updatedLayouts,
        public string $migrationPath,
    ) {}
}
