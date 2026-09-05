<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Concerns\User;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Exceptions\QueryAbortedException;
use CraftCms\Cms\Element\Queries\UserQuery;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * @internal
 */
trait QueriesAuthors
{
    /**
     * @var bool|null Whether to only return users that are authors of an entry.
     *                ---
     *                ```php
     *                // fetch all authors
     *                $authors = \CraftCms\Cms\User\Elements\User::find()
     *                ->authors()
     *                ->all();
     *                ```
     *                ```twig
     *                {# fetch all authors #}
     *                {% set authors = users()
     *                .authors()
     *                .all()%}
     *                ```
     *
     * @used-by authors()
     */
    public ?bool $authors = null;

    /**
     * @var ElementInterface|null The entry that the resulting users must be the author of.
     *
     * @used-by authorOf()
     */
    public ?ElementInterface $authorOf = null;

    protected function initQueriesAuthors(): void
    {
        $this->beforeQuery(function (UserQuery $userQuery) {
            static::applyAuthors($userQuery, $userQuery->authors);
            static::applyAuthorOf($userQuery, $userQuery->authorOf);
        });
    }

    public static function applyAuthors(Builder $query, ?bool $value): void
    {
        if (! is_bool($value)) {
            return;
        }

        $method = $value ? 'whereExists' : 'whereNotExists';

        $query->$method(DB::table(Table::ENTRIES_AUTHORS)->whereColumn('authorId', 'elements.id'));
    }

    public static function applyAuthorOf(Builder $query, ?ElementInterface $value): void
    {
        if (! $value) {
            return;
        }

        if (! $value->id) {
            throw new QueryAbortedException;
        }

        $query->whereExists(
            DB::table(Table::ENTRIES_AUTHORS, 'entries_authors')
                ->where('entryId', $value->id)
                ->whereColumn('entries_authors.authorId', 'users.id'),
        );
    }

    /**
     * Narrows the query results to only users that are authors of an entry.
     *
     * ---
     *
     * ```twig
     * {# Fetch authors #}
     * {% set {elements-var} = {twig-method}
     *   .authors()
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch authors
     * ${elements-var} = {element-class}::find()
     *     ->authors()
     *     ->all();
     * ```
     *
     * @param  bool|null  $value  The property value (defaults to true)
     *
     * @uses $authors
     */
    public function authors(?bool $value = true): self
    {
        $this->authors = $value;

        return $this;
    }

    /**
     * Narrows the query results to users who are the author of the given entry.
     *
     * @uses $authorOf
     */
    public function authorOf(?ElementInterface $value): self
    {
        $this->authorOf = $value;

        return $this;
    }
}
