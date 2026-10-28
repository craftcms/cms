<?php

declare(strict_types=1);

namespace craft\mcp\contentmodel\checks;

use craft\base\FieldInterface;
use craft\services\Fields;
use Illuminate\Support\Collection;

/** @since 5.12.0 */
class UnusedFields extends BaseCheck
{
    public function __construct(
        private readonly Fields $fields,
    ) {
    }

    public static function id(): string
    {
        return 'unused-fields';
    }

    protected function findings(): Collection
    {
        return collect($this->fields->getAllFields())
            ->filter(fn(FieldInterface $field): bool => $this->fields->findFieldUsages($field) === [])
            ->map($this->fieldFinding(...))
            ->values();
    }
}
