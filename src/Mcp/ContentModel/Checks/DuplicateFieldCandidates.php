<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\ContentModel\Checks;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Fields;
use Illuminate\Support\Collection;

/** @since 6.0.0 */
class DuplicateFieldCandidates extends BaseCheck
{
    public function __construct(
        private readonly Fields $fields,
    ) {}

    public static function id(): string
    {
        return 'duplicate-field-candidates';
    }

    protected function findings(): Collection
    {
        return $this->fields
            ->getAllFields()
            ->groupBy(fn (FieldInterface $field): string => $field::class.'|'.$this->normalizeName((string) $field->name))
            ->filter(static fn (Collection $fields): bool => $fields->count() > 1)
            ->map(function (Collection $fields): array {
                /** @var FieldInterface $first */
                $first = $fields->first();

                return [
                    'type' => $first::class,
                    'mergeable' => $fields->every(static fn (FieldInterface $field): bool => $field instanceof MergeableFieldInterface),
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
