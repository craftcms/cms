<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Element\Queries\Concerns\Tag;

use Craft;
use craft\models\TagGroup;
use CraftCms\Cms\Element\Queries\Exceptions\QueryAbortedException;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Query;
use CraftCms\Yii2Adapter\Database\DeprecatedTable;
use CraftCms\Yii2Adapter\Element\Queries\TagQuery;
use Illuminate\Support\Facades\DB;

/**
 * @internal
 */
trait QueriesTagGroups
{
    /**
     * @var mixed The group ID(s) that the resulting tags must be in.
     *            ---
     *            ```php
     *            // fetch tags in the Topics group
     *            $tags = \craft\elements\Tag::find()
     *            ->group('topics')
     *            ->all();
     *            ```
     *            ```twig
     *            {# fetch tags in the Topics group #}
     *            {% set tags = craft.tags()
     *            .group('topics')
     *            .all() %}
     *            ```
     *
     * @used-by group()
     * @used-by groupId()
     */
    public mixed $groupId = null;

    protected function initQueriesTagGroups(): void
    {
        $this->beforeQuery(function(TagQuery $tagQuery) {
            $this->normalizeGroupId($tagQuery);
            $this->applyGroupIdParam($tagQuery);
        });
    }

    /**
     * Narrows the query results based on the groups the tags belong to.
     *
     * Possible values include:
     *
     * | Value | Fetches tags…
     * | - | -
     * | `'foo'` | in a group with a handle of `foo`.
     * | `'not foo'` | not in a group with a handle of `foo`.
     * | `['foo', 'bar']` | in a group with a handle of `foo` or `bar`.
     * | `['not', 'foo', 'bar']` | not in a group with a handle of `foo` or `bar`.
     * | a [[TagGroup|TagGroup]] object | in a group represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch tags in the Foo group #}
     * {% set {elements-var} = {twig-method}
     *   .group('foo')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch tags in the Foo group
     * ${elements-var} = {php-method}
     *     ->group('foo')
     *     ->all();
     * ```
     *
     *
     * @uses $groupId
     */
    public function group(mixed $value): static
    {
        if (Query::normalizeParam($value, function($item) {
            if (is_string($item)) {
                $item = Craft::$app->getTags()->getTagGroupByHandle($item);
            }

            return $item instanceof TagGroup ? $item->id : null;
        })) {
            $this->groupId = $value;
        } else {
            $this->groupId = DB::table(DeprecatedTable::TAGGROUPS)
                ->whereParam('handle', $value)
                ->pluck('id')
                ->all() ?: false;
        }

        return $this;
    }

    /**
     * Narrows the query results based on the groups the tags belong to, per the groups’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches tags…
     * | - | -
     * | `1` | in a group with an ID of 1.
     * | `'not 1'` | not in a group with an ID of 1.
     * | `[1, 2]` | in a group with an ID of 1 or 2.
     * | `['not', 1, 2]` | not in a group with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch tags in the group with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *   .groupId(1)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch tags in the group with an ID of 1
     * ${elements-var} = {php-method}
     *     ->groupId(1)
     *     ->all();
     * ```
     *
     *
     * @uses $groupId
     */
    public function groupId(mixed $value): static
    {
        $this->groupId = $value;

        return $this;
    }

    /**
     * Applies the 'groupId' param to the query being prepared.
     */
    private function applyGroupIdParam(TagQuery $tagQuery): void
    {
        if (!$tagQuery->groupId) {
            return;
        }

        $tagQuery->whereIn('tags.groupId', $tagQuery->groupId);
    }

    /**
     * Normalizes the groupId param to an array of IDs or null
     *
     * @throws QueryAbortedException
     */
    private function normalizeGroupId(TagQuery $tagQuery): void
    {
        if ($tagQuery->groupId === false) {
            throw new QueryAbortedException();
        }

        $tagQuery->groupId = match (true) {
            empty($tagQuery->groupId) => null,
            is_numeric($tagQuery->groupId) => [$tagQuery->groupId],
            !is_array($tagQuery->groupId) || !Arr::isNumeric($tagQuery->groupId) => DB::table(DeprecatedTable::TAGGROUPS)
                ->whereNumericParam('id', $tagQuery->groupId)
                ->pluck('id')
                ->all(),
            default => $tagQuery->groupId,
        };
    }
}
