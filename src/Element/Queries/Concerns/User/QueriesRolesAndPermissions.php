<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Concerns\User;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Queries\UserQuery;
use CraftCms\Cms\Support\Arr;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Language\Alias;

/**
 * @internal
 */
trait QueriesRolesAndPermissions
{
    /**
     * @var bool|null Whether to only return users that are admins.
     *                ---
     *                ```php
     *                // fetch all the admins
     *                $admins = \CraftCms\Cms\User\Elements\User::find()
     *                ->admin(true)
     *                ->all();
     *
     * // fetch all the non-admins
     * $nonAdmins = \CraftCms\Cms\User\Elements\User::find()
     *     ->admin(false)
     *     ->all();
     * ```
     * ```twig
     * {# fetch all the admins #}
     * {% set admins = users()
     *   .admin()
     *   .all()%}
     *
     * {# fetch all the non-admins #}
     * {% set nonAdmins = users()
     *     .admin(false)
     *     .all() %}
     * ```
     *
     * @used-by admin()
     */
    public ?bool $admin = null;

    /**
     * @var mixed The permission that the resulting users must have.
     *            ---
     *            ```php
     *            // fetch users who can access the front end when maintenance mode is enabled
     *            $admins = \CraftCms\Cms\User\Elements\User::find()
     *            ->can('accessSiteWhenSystemIsOff')
     *            ->all();
     *            ```
     *            ```twig
     *            {# fetch users who can access the front end when maintenance mode is enabled #}
     *            {% set admins = users()
     *            .can('accessSiteWhenSystemIsOff')
     *            .all() %}
     *            ```
     *
     * @used-by can()
     */
    public mixed $can = null;

    protected function initQueriesRolesAndPermissions(): void
    {
        $this->beforeQuery(function (UserQuery $userQuery) {
            static::applyAdmin($userQuery, $userQuery->admin);

            if ($userQuery->admin !== true) {
                static::applyCan($userQuery, $userQuery->can);
            }
        });
    }

    public static function applyAdmin(Builder $query, ?bool $value): void
    {
        if (! is_bool($value)) {
            return;
        }

        $query->whereBool('users.admin', $value);
    }

    /**
     * Narrows the query results to only users that have admin accounts.
     *
     * ---
     *
     * ```twig
     * {# Fetch admins #}
     * {% set {elements-var} = {twig-method}
     *   .admin()
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch admins
     * ${elements-var} = {element-class}::find()
     *     ->admin()
     *     ->all();
     * ```
     *
     * @uses $admin
     */
    public function admin(bool $value = true): self
    {
        $this->admin = $value;

        return $this;
    }

    /**
     * Narrows the query results to only users that have a certain user permission, either directly on the user account or through one of their user groups.
     *
     * See [User Management](https://craftcms.com/docs/5.x/system/user-management.html) for a full list of available user permissions defined by Craft.
     *
     * ---
     *
     * ```twig
     * {# Fetch users who can access the front end when maintenance mode is enabled #}
     * {% set {elements-var} = {twig-method}
     *   .can('accessSiteWhenSystemIsOff')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch users who can access the front end when maintenance mode is enabled
     * ${elements-var} = {element-class}::find()
     *     ->can('accessSiteWhenSystemIsOff')
     *     ->all();
     * ```
     *
     * @param  mixed  $value  The property value
     *
     * @uses $can
     */
    public function can(mixed $value): self
    {
        $this->can = $value;

        return $this;
    }

    public static function applyCan(Builder $query, mixed $value): void
    {
        if ($value !== false && empty($value)) {
            return;
        }

        if (is_string($value) && ! is_numeric($value)) {
            // Convert it to the actual permission ID, or false if the permission doesn't have an ID yet.
            $value = DB::table(Table::USERPERMISSIONS)
                ->where('name', strtolower($value))
                ->select('id')
                ->value('id') ?? false;
        }

        // False means that the permission doesn't have an ID yet.
        $permittedUserIds = collect();

        if ($value !== false) {
            // Get the users that have that permission directly
            $permittedUserIds = DB::table(Table::USERPERMISSIONS_USERS)
                ->whereIn('permissionId', Arr::wrap($value))
                ->pluck('userId');

            // Get the users that have that permission via a user group
            $permittedUserIdsViaGroups = DB::table(Table::USERGROUPS_USERS, 'g_u')
                ->select('g_u.userId')
                ->join(new Alias(Table::USERPERMISSIONS_USERGROUPS, 'p_g'), 'p_g.groupId', '=', 'g_u.groupId')
                ->whereIn('p_g.permissionId', Arr::wrap($value))
                ->pluck('userId');

            $permittedUserIds = $permittedUserIds->merge($permittedUserIdsViaGroups)->unique();
        }

        if ($permittedUserIds->isEmpty()) {
            $query->whereBool('users.admin', true);
        } else {
            $query->where(function (Builder $q) use ($permittedUserIds) {
                $q->whereBool('users.admin', true)
                    ->orWhereIn('users.id', $permittedUserIds);
            });
        }
    }
}
