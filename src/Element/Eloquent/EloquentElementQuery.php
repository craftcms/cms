<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Eloquent;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Override;

/**
 * Queries {@see EloquentElement}s, selecting their model's columns alongside the element's.
 *
 * The model is left-joined onto `elements` through Craft's `elements_models`
 * links, so elements without a model row (unpublished drafts) are still found.
 *
 * @template TElement of EloquentElement
 *
 * @extends ElementQuery<TElement>
 *
 * @since 6.0.0
 */
class EloquentElementQuery extends ElementQuery
{
    private const string MODEL_COLUMN_PREFIX = 'model:';

    /**
     * @param  class-string<TElement>  $elementType
     * @param  array<string, mixed>  $config
     * @param  bool  $modelScopes  Whether to apply the model's global scopes. Without them, the query finds
     *                             elements whose models they'd hide — or that are gone. Only settable here,
     *                             where a request's query criteria can't reach it.
     */
    public function __construct(string $elementType, array $config = [], private readonly bool $modelScopes = true)
    {
        parent::__construct($elementType, $config);

        $table = $elementType::newModel()->getTable();

        $this->query->addSelect(array_map(
            fn (string $column) => "$table.$column as ".self::MODEL_COLUMN_PREFIX.$column,
            $elementType::modelColumns(),
        ));

        $this->beforeQuery(fn (self $query) => $query->joinModel());
    }

    /**
     * Joins the model through its links, as its own Eloquent query sees it — with its
     * global scopes, so a tenant scope, say, holds here too — and leaves out linked
     * elements whose models those scopes hide.
     *
     * Soft-deleted models are still joined: the element's own trash mirrors theirs.
     */
    private function joinModel(): void
    {
        $model = $this->elementType::newModel();
        $table = $model->getTable();

        $modelQuery = $this->modelScopes
            ? $model->newQuery()->withoutGlobalScope(SoftDeletingScope::class)
            : $model->newQueryWithoutScopes();

        if ($modelQuery->getQuery()->columns === null) {
            $modelQuery->select("$table.*");
        }

        [$modelKey, $linkKey] = app(ModelLinks::class)->joinColumns($model);

        $this->query
            ->leftJoin(Table::ELEMENTS_MODELS, Table::ELEMENTS_MODELS.'.elementId', '=', 'elements.id')
            ->leftJoinSub($modelQuery->toBase(), $table, $modelKey, '=', $linkKey)
            ->when($this->modelScopes, fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->whereNull(Table::ELEMENTS_MODELS.'.elementId')
                ->orWhereNotNull($model->getQualifiedKeyName())));
    }

    /**
     * Falls back to the element type's code-defined layout, so its custom fields can be queried by handle.
     */
    #[Override]
    protected function fieldLayouts(): Collection
    {
        $fieldLayouts = parent::fieldLayouts();

        return $fieldLayouts->isNotEmpty() ? $fieldLayouts : collect([$this->elementType::resolveFieldLayout()]);
    }

    /** @param array<string, mixed> $row */
    #[Override]
    public function createElement(array $row): ElementInterface
    {
        $attributes = [];

        foreach ($row as $key => $value) {
            if (str_starts_with($key, self::MODEL_COLUMN_PREFIX)) {
                $attributes[substr($key, strlen(self::MODEL_COLUMN_PREFIX))] = $value;
                unset($row[$key]);
            }
        }

        $model = $this->elementType::newModel();

        if (array_filter($attributes, fn (mixed $value) => $value !== null) !== []) {
            $model = $model->newFromBuilder($attributes, $model->getConnection()->getName());
        }

        $element = parent::createElement($row);

        if ($element instanceof EloquentElement) {
            $element->setModel($model);
        }

        return $element;
    }
}
