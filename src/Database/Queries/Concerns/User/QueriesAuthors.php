<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Concerns\User;

use craft\base\ElementInterface;
use CraftCms\Cms\Database\Queries\Exceptions\QueryAbortedException;
use CraftCms\Cms\Database\Queries\UserQuery;
use CraftCms\Cms\Database\Table;
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
            if (is_bool($userQuery->authors)) {
                $method = $userQuery->authors ? 'whereExists' : 'whereNotExists';

                $userQuery->subQuery->$method(DB::table(Table::ENTRIES_AUTHORS)->whereColumn('authorId', 'elements.id'));
            }

            if ($userQuery->authorOf) {
                if (! $userQuery->authorOf->id) {
                    throw new QueryAbortedException;
                }

                $userQuery->subQuery->whereExists(
                    DB::table(Table::ENTRIES_AUTHORS, 'entries_authors')
                        ->where('entryId', $this->authorOf->id)
                        ->whereColumn('entries_authors.authorId', 'users.id'),
                );
            }
        });
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
