<?php

declare(strict_types=1);

namespace craft\mcp\contentmodel\checks;

use craft\base\FieldInterface;
use craft\base\MergeableFieldInterface;
use craft\services\Fields;
use Illuminate\Support\Collection;

/** @since 5.12.0 */
class DuplicateFieldCandidates extends BaseCheck
{
    public function __construct(
        private readonly Fields $fields,
    ) {
    }

    public static function id(): string
    {
        return 'duplicate-field-candidates';
    }

    protected function findings(): Collection
    {
        return collect($this->fields->getAllFields())
            ->groupBy(fn(FieldInterface $field): string => $field::class . '|' . $this->normalizeName((string) $field->name))
            ->filter(static fn(Collection $fields): bool => $fields->count() > 1)
            ->map(function(Collection $fields): array {
                /** @var FieldInterface $first */
                $first = $fields->first();

                return [
                    'type' => $first::class,
                    'mergeable' => $fields->every(static fn(FieldInterface $field): bool => $field instanceof MergeableFieldInterface),
                    'fields' => $fields->map($this->fieldFinding(...))->values()->all(),
                ];
            })
            ->values();
    }

    private function normalizeName(string $name): string
    {
        return (string) preg_replace('/[^\p{L}]/u', '', mb_strtolower($name));
    }
}
