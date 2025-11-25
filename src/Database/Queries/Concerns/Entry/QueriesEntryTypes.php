<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Concerns\Entry;

use craft\helpers\Db as DbHelper;
use CraftCms\Cms\Database\Queries\EntryQuery;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\EntryTypes;
use Illuminate\Support\Facades\DB;

/**
 * @internal
 */
trait QueriesEntryTypes
{
    /**
     * @var mixed The entry type ID(s) that the resulting entries must have.
     *            ---
     *            ```php{4}
     *            // fetch Article entries in the News section
     *            $entries = \craft\elements\Entry::find()
     *            ->section('news')
     *            ->type('article')
     *            ->all();
     *            ```
     *            ```twig{4}
     *            {# fetch entries in the News section #}
     *            {% set entries = entries()
     *            .section('news')
     *            .type('article')
     *            .all() %}
     *            ```
     *
     * @used-by EntryQuery::type()
     * @used-by typeId()
     */
    public mixed $typeId = null;

    protected function initQueriesEntryTypes(): void
    {
        $this->beforeQuery(function (EntryQuery $entryQuery) {
            $this->normalizeTypeId($entryQuery);

            if ($entryQuery->typeId === []) {
                return;
            }

            if (! $entryQuery->typeId) {
                return;
            }

            $entryQuery->subQuery->whereIn('entries.typeId', $entryQuery->typeId);
        });
    }

    /**
     * Narrows the query results based on the entries’ entry types.
     *
     * Possible values include:
     *
     * | Value | Fetches entries…
     * | - | -
     * | `'foo'` | of a type with a handle of `foo`.
     * | `'not foo'` | not of a type with a handle of `foo`.
     * | `['foo', 'bar']` | of a type with a handle of `foo` or `bar`.
     * | `['not', 'foo', 'bar']` | not of a type with a handle of `foo` or `bar`.
     * | an [[EntryType|EntryType]] object | of a type represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch entries in the Foo section with a Bar entry type #}
     * {% set {elements-var} = {twig-method}
     *   .section('foo')
     *   .type('bar')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch entries in the Foo section with a Bar entry type
     * ${elements-var} = {php-method}
     *     ->section('foo')
     *     ->type('bar')
     *     ->all();
     * ```
     *
     *
     * @uses $typeId
     */
    public function type(mixed $value): static
    {
        if (DbHelper::normalizeParam($value, function ($item) {
            if (is_string($item)) {
                $item = EntryTypes::getEntryTypeByHandle($item);
            }

            return $item instanceof EntryType ? $item->id : null;
        })) {
            $this->typeId = $value;
        } else {
            $this->typeId = DB::table(Table::ENTRYTYPES)
                ->whereParam('handle', $value)
                ->pluck('id')
                ->all();
        }

        return $this;
    }

    /**
     * Narrows the query results based on the entries’ entry types, per the types’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches entries…
     * | - | -
     * | `1` | of a type with an ID of 1.
     * | `'not 1'` | not of a type with an ID of 1.
     * | `[1, 2]` | of a type with an ID of 1 or 2.
     * | `['not', 1, 2]` | not of a type with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch entries of the entry type with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *   .typeId(1)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch entries of the entry type with an ID of 1
     * ${elements-var} = {php-method}
     *     ->typeId(1)
     *     ->all();
     * ```
     *
     *
     * @uses $typeId
     */
    public function typeId(mixed $value): static
    {
        $this->typeId = $value;

        return $this;
    }

    private function normalizeTypeId(EntryQuery $entryQuery): void
    {
        $entryQuery->typeId = match (true) {
            empty($entryQuery->typeId) => is_array($entryQuery->typeId) ? [] : null,
            is_numeric($entryQuery->typeId) => [$entryQuery->typeId],
            ! is_array($entryQuery->typeId) || ! Arr::isNumeric($entryQuery->typeId) => DB::table(Table::ENTRYTYPES)
                ->whereNumericParam('id', $entryQuery->typeId)
                ->pluck('id')
                ->all(),
            default => $entryQuery->typeId,
        };
    }
}
