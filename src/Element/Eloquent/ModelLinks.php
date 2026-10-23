<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Eloquent;

use CraftCms\Cms\Database\Table;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

/**
 * Records which Eloquent model row each {@see EloquentElement} represents.
 *
 * The links live in Craft's own `elements_models` table, so the model and its table stay unchanged.
 *
 * @since 6.0.0
 */
#[Singleton]
class ModelLinks
{
    public function elementId(Model $model): ?int
    {
        if ($model->getKey() === null) {
            return null;
        }

        $elementId = DB::table(Table::ELEMENTS_MODELS)
            ->where('modelType', $model->getMorphClass())
            ->where('modelKey', (string) $model->getKey())
            ->value('elementId');

        return $elementId === null ? null : (int) $elementId;
    }

    /**
     * @param  list<int|string>  $keys
     * @return list<string> Which of a model class's keys are linked to an element
     */
    public function linkedKeys(Model $model, array $keys): array
    {
        return DB::table(Table::ELEMENTS_MODELS)
            ->where('modelType', $model->getMorphClass())
            ->whereIn('modelKey', array_map(strval(...), $keys))
            ->pluck('modelKey')
            ->all();
    }

    /**
     * Moves a model's link to its new key, after the key itself has changed.
     */
    public function rekey(Model $model, int|string $originalKey): void
    {
        DB::table(Table::ELEMENTS_MODELS)
            ->where('modelType', $model->getMorphClass())
            ->where('modelKey', (string) $originalKey)
            ->update(['modelKey' => (string) $model->getKey()]);
    }

    public function link(int $elementId, Model $model): void
    {
        DB::table(Table::ELEMENTS_MODELS)->upsert([
            'elementId' => $elementId,
            'modelType' => $model->getMorphClass(),
            'modelKey' => (string) $model->getKey(),
        ], ['elementId'], ['modelType', 'modelKey']);
    }

    /**
     * @param  list<int>  $elementIds
     * @return array<int, string> The linked models' keys, indexed by element ID
     */
    public function modelKeys(array $elementIds): array
    {
        return DB::table(Table::ELEMENTS_MODELS)
            ->whereIn('elementId', $elementIds)
            ->pluck('modelKey', 'elementId')
            ->all();
    }

    /**
     * The two sides of the join between a model's table and its links: the model's
     * key and the link's `modelKey`, made comparable.
     *
     * The link stores keys as strings, so an integer key compares against it cast
     * to an integer, and any other key — a PostgreSQL `uuid`, say — as text.
     *
     * @return array{0: Expression<*>|string, 1: Expression<*>}
     */
    public function joinColumns(Model $model): array
    {
        $grammar = DB::getQueryGrammar();
        $modelKey = $model->getQualifiedKeyName();
        $linkKey = $grammar->wrap(Table::ELEMENTS_MODELS.'.modelKey');

        if ($model->getKeyType() === 'int') {
            $type = match (DB::getDriverName()) {
                'mysql', 'mariadb' => 'SIGNED',
                'pgsql' => 'BIGINT',
                default => 'INTEGER',
            };

            return [$modelKey, new Expression("CAST($linkKey AS $type)")];
        }

        if (DB::getDriverName() === 'pgsql') {
            return [new Expression('CAST('.$grammar->wrap($modelKey).' AS TEXT)'), new Expression($linkKey)];
        }

        return [$modelKey, new Expression($linkKey)];
    }
}
