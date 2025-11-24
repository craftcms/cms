<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Concerns\User;

use Craft;
use craft\models\UserGroup;
use CraftCms\Cms\Database\Queries\UserQuery;
use CraftCms\Cms\Database\QueryParam;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Query;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @internal
 */
trait QueriesUserGroups
{
    /**
     * @var mixed The user group ID(s) that the resulting users must belong to.
     *            ---
     *            ```php
     *            // fetch the authors
     *            $admins = \craft\elements\User::find()
     *            ->group('authors')
     *            ->all();
     *            ```
     *            ```twig
     *            {# fetch the authors #}
     *            {% set admins = craft.users()
     *            .group('authors')
     *            .all() %}
     *            ```
     *
     * @used-by group()
     * @used-by groupId()
     */
    public mixed $groupId = null;

    /**
     * @var bool Whether the users’ groups should be eager-loaded.
     *           ---
     *           ```php
     *           // fetch users with their user groups
     *           $users = \craft\elements\User::find()
     *           ->withGroups()
     *           ->all();
     *           ```
     *           ```twig
     *           {# fetch users with their user groups #}
     *           {% set users = craft.users()
     *           .withGroups()
     *           .all() %}
     *           ```
     *
     * @used-by withGroups()
     */
    public bool $withGroups = false;

    protected function initQueriesUserGroups(): void
    {
        $this->beforeQuery(function (UserQuery $userQuery) {
            if (! $userQuery->groupId) {
                return;
            }

            // Checking multiple groups?
            if (
                is_array($userQuery->groupId) &&
                is_string(reset($userQuery->groupId)) &&
                strtolower(reset($userQuery->groupId)) === 'and'
            ) {
                $groupIdChecks = array_slice($userQuery->groupId, 1);
            } else {
                $groupIdChecks = [$userQuery->groupId];
            }

            foreach ($groupIdChecks as $i => $groupIdCheck) {
                if (
                    is_array($groupIdCheck) &&
                    is_string(reset($groupIdCheck)) &&
                    strtolower(reset($groupIdCheck)) === 'not'
                ) {
                    $groupIdOperator = 'whereNotExists';
                    array_shift($groupIdCheck);
                    if (empty($groupIdCheck)) {
                        continue;
                    }
                } else {
                    $groupIdOperator = 'whereExists';
                }

                $userQuery->subQuery->$groupIdOperator(
                    DB::table(Table::USERGROUPS_USERS, "ugu$i")
                        ->whereColumn('elements.id', "ugu$i.userId")
                        ->whereNumericParam('groupId', $groupIdCheck),
                );
            }
        });

        $this->afterQuery(function (mixed $result) {
            if (! $result instanceof Collection) {
                return $result;
            }

            // Eager-load transforms?
            if (! $this->withGroups) {
                return $result;
            }

            if ($this->asArray) {
                return $result;
            }

            if (Edition::get()->value < Edition::Pro->value) {
                return $result;
            }

            Craft::$app->getUserGroups()->eagerLoadGroups($result->all());

            return $result;
        });
    }

    /**
     * Narrows the query results based on the user group the users belong to.
     *
     * Possible values include:
     *
     * | Value | Fetches users…
     * | - | -
     * | `'foo'` | in a group with a handle of `foo`.
     * | `'not foo'` | not in a group with a handle of `foo`.
     * | `['foo', 'bar']` | in a group with a handle of `foo` or `bar`.
     * | `['and', 'foo', 'bar']` | in both groups with handles of `foo` or `bar`.
     * | `['not', 'foo', 'bar']` | not in a group with a handle of `foo` or `bar`.
     * | a [[UserGroup|UserGroup]] object | in a group represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch users in the Foo user group #}
     * {% set {elements-var} = {twig-method}
     *   .group('foo')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch users in the Foo user group
     * ${elements-var} = {php-method}
     *     ->group('foo')
     *     ->all();
     * ```
     *
     * @param  mixed  $value  The property value
     *
     * @uses $groupId
     */
    public function group(mixed $value): self
    {
        // If the value is a group handle, swap it with the user group
        if (is_string($value) && ($group = Craft::$app->getUserGroups()->getGroupByHandle($value))) {
            $value = $group;
        }

        if (Query::normalizeParam($value,
            fn ($item) => $item instanceof UserGroup ? $item->id : null)) {
            $this->groupId = $value;
        } else {
            $operator = QueryParam::extractOperator($value);
            $this->groupId = DB::table(Table::USERGROUPS)
                ->whereParam('handle', $value)
                ->pluck('id')
                ->all();

            if ($this->groupId && $operator !== null) {
                array_unshift($this->groupId, $operator);
            }
        }

        return $this;
    }

    /**
     * Narrows the query results based on the user group the users belong to, per the groups’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches users…
     * | - | -
     * | `1` | in a group with an ID of 1.
     * | `'not 1'` | not in a group with an ID of 1.
     * | `[1, 2]` | in a group with an ID of 1 or 2.
     * | `['and', 1, 2]` | in both groups with IDs of 1 or 2.
     * | `['not', 1, 2]` | not in a group with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch users in a group with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *   .groupId(1)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch users in a group with an ID of 1
     * ${elements-var} = {php-method}
     *     ->groupId(1)
     *     ->all();
     * ```
     *
     * @param  mixed  $value  The property value
     *
     * @uses $groupId
     */
    public function groupId(mixed $value): self
    {
        $this->groupId = $value;

        return $this;
    }

    /**
     * Causes the query to return matching users eager-loaded with their user groups.
     *
     * Possible values include:
     *
     * | Value | Fetches users…
     * | - | -
     * | `'>= 2018-04-01'` | that last logged-in on or after 2018-04-01.
     * | `'< 2018-05-01'` | that last logged-in before 2018-05-01
     * | `['and', '>= 2018-04-04', '< 2018-05-01']` | that last logged-in between 2018-04-01 and 2018-05-01.
     *
     * ---
     *
     * ```php
     * // fetch users with their user groups
     * $users = \craft\elements\User::find()
     *     ->withGroups()
     *     ->all();
     * ```
     *
     * ```twig
     * {# fetch users with their user groups #}
     * {% set users = craft.users()
     *   .withGroups()
     *   .all() %}
     * ```
     *
     * @uses $withGroups
     */
    public function withGroups(bool $value = true): self
    {
        $this->withGroups = $value;

        return $this;
    }
}
