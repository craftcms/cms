<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Concerns;

use craft\models\UserGroup;
use CraftCms\Cms\Database\Queries\EntryQuery;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Language\Alias;

/**
 * @internal
 */
trait QueriesAuthors
{
    /**
     * @var mixed The user ID(s) that the resulting entries’ authors must have.
     *
     * @used-by authorId()
     */
    public mixed $authorId = null;

    /**
     * @var mixed The user group ID(s) that the resulting entries’ authors must be in.
     *            ---
     *            ```php
     *            // fetch entries authored by people in the Authors group
     *            $entries = \craft\elements\Entry::find()
     *            ->authorGroup('authors')
     *            ->all();
     *            ```
     *            ```twig
     *            {# fetch entries authored by people in the Authors group #}
     *            {% set entries = craft.entries()
     *            .authorGroup('authors')
     *            .all() %}
     *            ```
     *
     * @used-by authorGroup()
     * @used-by authorGroupId()
     */
    public mixed $authorGroupId = null;

    protected function initQueriesAuthors(): void
    {
        $this->beforeQuery(function (EntryQuery $query) {
            if ($this->authorGroupId === []) {
                return;
            }

            if (Edition::get() === Edition::Solo) {
                return;
            }

            $this->applyAuthorId($query);
            $this->applyAuthorGroupId($query);
        });
    }

    private function applyAuthorId(EntryQuery $query): void
    {
        if (! $query->authorId) {
            return;
        }

        // Checking multiple authors?
        if (
            is_array($query->authorId) &&
            is_string(reset($query->authorId)) &&
            strtolower(reset($query->authorId)) === 'and'
        ) {
            $authorIdChecks = array_slice($query->authorId, 1);
        } else {
            $authorIdChecks = [$query->authorId];
        }

        foreach ($authorIdChecks as $authorIdCheck) {
            if (
                is_array($authorIdCheck) &&
                is_string(reset($authorIdCheck)) &&
                strtolower(reset($authorIdCheck)) === 'not'
            ) {
                $authorIdOperator = 'whereNotExists';
                array_shift($authorIdCheck);
                if (empty($authorIdCheck)) {
                    continue;
                }
            } else {
                $authorIdOperator = 'whereExists';
            }

            $query->subQuery->$authorIdOperator(
                DB::table(Table::ENTRIES_AUTHORS, 'entries_authors')
                    ->whereColumn('entries.id', 'entries_authors.entryId')
                    ->whereNumericParam('authorId', $authorIdCheck),
            );
        }
    }

    private function applyAuthorGroupId(EntryQuery $query): void
    {
        if (! $query->authorGroupId) {
            return;
        }

        $query->subQuery->whereExists(
            DB::table(Table::ENTRIES_AUTHORS, 'entries_authors')
                ->join(new Alias(Table::USERGROUPS_USERS, 'usergroups_users'), 'usergroups_users.userId', '=',
                    'entries_authors.authorId')
                ->whereColumn('entries.id', 'entries_authors.entryId')
                ->whereNumericParam('usergroups_users.groupId', $query->authorGroupId),
        );
    }

    /**
     * Narrows the query results based on the entries’ author ID(s).
     *
     * Possible values include:
     *
     * | Value | Fetches entries…
     * | - | -
     * | `1` | with an author with an ID of 1.
     * | `'not 1'` | not with an author with an ID of 1.
     * | `[1, 2]` | with an author with an ID of 1 or 2.
     * | `['and', 1, 2]` |  with authors with IDs of 1 and 2.
     * | `['not', 1, 2]` | not with an author with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch entries with an author with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *   .authorId(1)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch entries with an author with an ID of 1
     * ${elements-var} = {php-method}
     *     ->authorId(1)
     *     ->all();
     * ```
     *
     * @uses $authorId
     */
    public function authorId(mixed $value): static
    {
        $this->authorId = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the user group the entries’ authors belong to.
     *
     * Possible values include:
     *
     * | Value | Fetches entries…
     * | - | -
     * | `'foo'` | with an author in a group with a handle of `foo`.
     * | `'not foo'` | not with an author in a group with a handle of `foo`.
     * | `['foo', 'bar']` | with an author in a group with a handle of `foo` or `bar`.
     * | `['not', 'foo', 'bar']` | not with an author in a group with a handle of `foo` or `bar`.
     * | a [[UserGroup|UserGroup]] object | with an author in a group represented by the object.
     * | an array of [[UserGroup|UserGroup]] objects | with an author in a group represented by the objects.
     *
     * ---
     *
     * ```twig
     * {# Fetch entries with an author in the Foo user group #}
     * {% set {elements-var} = {twig-method}
     *   .authorGroup('foo')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch entries with an author in the Foo user group
     * ${elements-var} = {php-method}
     *     ->authorGroup('foo')
     *     ->all();
     * ```
     *
     *
     * @uses $authorGroupId
     */
    public function authorGroup(mixed $value): static
    {
        if ($value instanceof UserGroup) {
            $this->authorGroupId = $value->id;

            return $this;
        }

        if (is_iterable($value)) {
            $collection = collect($value);
            if ($collection->every(fn ($v) => $v instanceof UserGroup)) {
                $this->authorGroupId = $collection->pluck('id')->all();

                return $this;
            }
        }

        if ($value !== null) {
            $this->authorGroupId = DB::table(Table::USERGROUPS)
                ->whereParam('handle', $value)
                ->pluck('id')
                ->all();
        } else {
            $this->authorGroupId = null;
        }

        return $this;
    }

    /**
     * Narrows the query results based on the user group the entries’ authors belong to, per the groups’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches entries…
     * | - | -
     * | `1` | with an author in a group with an ID of 1.
     * | `'not 1'` | not with an author in a group with an ID of 1.
     * | `[1, 2]` | with an author in a group with an ID of 1 or 2.
     * | `['not', 1, 2]` | not with an author in a group with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch entries with an author in a group with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *   .authorGroupId(1)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch entries with an author in a group with an ID of 1
     * ${elements-var} = {php-method}
     *     ->authorGroupId(1)
     *     ->all();
     * ```
     *
     *
     * @uses $authorGroupId
     */
    public function authorGroupId(mixed $value): static
    {
        $this->authorGroupId = $value;

        return $this;
    }
}
