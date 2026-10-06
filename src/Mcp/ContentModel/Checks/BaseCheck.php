<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\ContentModel\Checks;

use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Mcp\ContentModel\Check;
use CraftCms\Cms\Section\Data\Section;
use Illuminate\Support\Collection;

/**
 * Shared implementation for Craft's built-in content-model checks.
 *
 * Plugins only need to implement {@see Check}; extending this class is optional.
 *
 * @since 6.0.0
 */
abstract class BaseCheck implements Check
{
    /** @return Collection<int, mixed> */
    abstract protected function findings(): Collection;

    public function run(int $limit): array
    {
        $findings = $this->findings();

        return [
            'count' => $findings->count(),
            'findings' => $findings->take($limit)->values()->all(),
        ];
    }

    /** @return array<int, bool> */
    protected function entryIdsBy(string $column): array
    {
        return Entry::find()
            ->site('*')
            ->unique()
            ->status(null)
            ->pluck($column)
            ->filter(static fn (mixed $id): bool => is_numeric($id))
            ->mapWithKeys(static fn (mixed $id): array => [(int) $id => true])
            ->all();
    }

    /** @return array{id: int|null, uid: string|null, handle: string, name: string} */
    protected function fieldFinding(FieldInterface $field): array
    {
        return [
            'id' => $field->getId(),
            'uid' => $field->uid,
            'handle' => (string) $field->handle,
            'name' => (string) $field->name,
        ];
    }

    /** @return array{id: int|null, uid: string|null, handle: string, name: string} */
    protected function modelFinding(EntryType|Section $model): array
    {
        return [
            'id' => $model->id,
            'uid' => $model->uid,
            'handle' => (string) $model->handle,
            'name' => (string) $model->name,
        ];
    }
}
