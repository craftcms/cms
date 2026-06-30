<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Element\Queries\Concerns\Category;

use Craft;
use craft\models\CategoryGroup;
use CraftCms\Cms\Element\Queries\Exceptions\QueryAbortedException;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Query;
use CraftCms\Yii2Adapter\Database\DeprecatedTable;
use CraftCms\Yii2Adapter\Element\Queries\CategoryQuery;
use Illuminate\Support\Facades\DB;

/**
 * @internal
 */
trait QueriesCategoryGroups
{
    /**
     * @var mixed The group ID(s) that the resulting categories must be in.
     *            ---
     *            ```php
     *            // fetch categories in the News group
     *            $categories = \craft\elements\Category::find()
     *            ->group('news')
     *            ->all();
     *            ```
     *            ```twig
     *            {# fetch categories in the News group #}
     *            {% set categories = craft.categories()
     *            .group('news')
     *            .all() %}
     *            ```
     *
     * @used-by group()
     * @used-by groupId()
     */
    public mixed $groupId = null;

    protected function initQueriesGroups(): void
    {
        $this->beforeQuery(function(CategoryQuery $categoryQuery) {
            $this->normalizeGroupId($categoryQuery);

            if ($categoryQuery->groupId === []) {
                throw new QueryAbortedException();
            }

            $this->applyGroupIdParam($categoryQuery);
        });
    }

    /**
     * Narrows the query results based on the groups the categories belong to.
     *
     * Possible values include:
     *
     * | Value | Fetches categories…
     * | - | -
     * | `'foo'` | in a group with a handle of `foo`.
     * | `'not foo'` | not in a group with a handle of `foo`.
     * | `['foo', 'bar']` | in a group with a handle of `foo` or `bar`.
     * | `['not', 'foo', 'bar']` | not in a group with a handle of `foo` or `bar`.
     * | a [[CategoryGroup|CategoryGroup]] object | in a group represented by the object.
     * | `'*'` | in any group.
     *
     * ---
     *
     * ```twig
     * {# Fetch categories in the Foo group #}
     * {% set {elements-var} = {twig-method}
     *   .group('foo')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch categories in the Foo group
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
        // If the value is a group handle, swap it with the group
        if (is_string($value) && ($group = Craft::$app->getCategories()->getGroupByHandle($value))) {
            $value = $group;
        }

        if ($value instanceof CategoryGroup) {
            $this->groupId = [$value->id];
            $this->structureId = $value->structureId;
        } elseif ($value === '*') {
            $this->groupId = Craft::$app->getCategories()->getAllGroupIds();
        } elseif (Query::normalizeParam($value, function($item) {
            if (is_string($item)) {
                $item = Craft::$app->getCategories()->getGroupByHandle($item);
            }

            return $item instanceof CategoryGroup ? $item->id : null;
        })) {
            $this->groupId = $value;
        } else {
            $this->groupId = DB::table(DeprecatedTable::CATEGORYGROUPS)
                ->whereParam('handle', $value)
                ->pluck('id')
                ->all();
        }

        return $this;
    }

    /**
     * Narrows the query results based on the groups the categories belong to, per the groups’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches categories…
     * | - | -
     * | `1` | in a group with an ID of 1.
     * | `'not 1'` | not in a group with an ID of 1.
     * | `[1, 2]` | in a group with an ID of 1 or 2.
     * | `['not', 1, 2]` | not in a group with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch categories in the group with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *   .groupId(1)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch categories in the group with an ID of 1
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
    private function applyGroupIdParam(CategoryQuery $categoryQuery): void
    {
        if (!$categoryQuery->groupId) {
            return;
        }

        $categoryQuery->whereIn('categories.groupId', $categoryQuery->groupId);

        // Should we set the structureId param?
        if (
            $categoryQuery->withStructure !== false &&
            !isset($categoryQuery->structureId) &&
            count($categoryQuery->groupId) === 1
        ) {
            $group = Craft::$app->getCategories()->getGroupById(reset($categoryQuery->groupId));
            if ($group) {
                $categoryQuery->structureId = $group->structureId;
            }
        }
    }

    /**
     * Normalizes the groupId param to an array of IDs or null
     */
    private function normalizeGroupId(CategoryQuery $categoryQuery): void
    {
        $categoryQuery->groupId = match (true) {
            empty($categoryQuery->groupId) => is_array($categoryQuery->groupId) ? [] : null,
            is_numeric($categoryQuery->groupId) => [$categoryQuery->groupId],
            !is_array($categoryQuery->groupId) || !Arr::isNumeric($categoryQuery->groupId) => DB::table(DeprecatedTable::CATEGORYGROUPS)
                ->whereNumericParam('id', $categoryQuery->groupId)
                ->pluck('id')
                ->all(),
            default => $categoryQuery->groupId,
        };
    }
}
