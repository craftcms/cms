<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\ContentModel\Checks;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Fields;
use Illuminate\Support\Collection;

/** @since 6.0.0 */
class UnusedFields extends BaseCheck
{
    public function __construct(
        private readonly Fields $fields,
    ) {}

    public static function id(): string
    {
        return 'unused-fields';
    }

    protected function findings(): Collection
    {
        return $this->fields
            ->getAllFields()
            ->filter(fn (FieldInterface $field): bool => $this->fields->findFieldUsages($field)->isEmpty())
            ->map($this->fieldFinding(...))
            ->values();
    }
}
