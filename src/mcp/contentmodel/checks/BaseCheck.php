<?php

declare(strict_types=1);

namespace craft\mcp\contentmodel\checks;

use craft\base\FieldInterface;
use craft\elements\Entry;
use craft\mcp\contentmodel\Check;
use craft\models\EntryType;
use craft\models\Section;
use Illuminate\Support\Collection;

/**
 * Shared implementation for Craft's built-in content-model checks.
 *
 * Plugins only need to implement {@see Check}; extending this class is optional.
 *
 * @since 5.12.0
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
        return collect(Entry::find()
            ->site('*')
            ->unique()
            ->status(null)
            ->select([$column])->column())
            ->filter(static fn(mixed $id): bool => is_numeric($id))
            ->mapWithKeys(static fn(mixed $id): array => [(int) $id => true])
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
