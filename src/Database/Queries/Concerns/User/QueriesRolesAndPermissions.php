<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Concerns\User;

use CraftCms\Cms\Database\Queries\UserQuery;
use CraftCms\Cms\Database\Table;
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
     *                $admins = \craft\elements\User::find()
     *                ->admin(true)
     *                ->all();
     *
     * // fetch all the non-admins
     * $nonAdmins = \craft\elements\User::find()
     *     ->admin(false)
     *     ->all();
     * ```
     * ```twig
     * {# fetch all the admins #}
     * {% set admins = craft.users()
     *   .admin()
     *   .all()%}
     *
     * {# fetch all the non-admins #}
     * {% set nonAdmins = craft.users()
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
     *            // fetch users who can access the front end when the system is offline
     *            $admins = \craft\elements\User::find()
     *            ->can('accessSiteWhenSystemIsOff')
     *            ->all();
     *            ```
     *            ```twig
     *            {# fetch users who can access the front end when the system is offline #}
     *            {% set admins = craft.users()
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
            if (is_bool($userQuery->admin)) {
                $userQuery->subQuery->where('users.admin', $userQuery->admin);
            }

            if ($this->admin !== true) {
                $this->applyCanParam($userQuery);
            }
        });
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
     * {# Fetch users who can access the front end when the system is offline #}
     * {% set {elements-var} = {twig-method}
     *   .can('accessSiteWhenSystemIsOff')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch users who can access the front end when the system is offline
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

    /**
     * Applies the 'can' param to the query being prepared.
     */
    private function applyCanParam(UserQuery $userQuery): void
    {
        if ($this->can !== false && empty($this->can)) {
            return;
        }

        if (is_string($this->can) && ! is_numeric($this->can)) {
            // Convert it to the actual permission ID, or false if the permission doesn't have an ID yet.
            $this->can = DB::table(Table::USERPERMISSIONS)
                ->where('name', strtolower($this->can))
                ->select('id')
                ->value('id') ?? false;
        }

        // False means that the permission doesn't have an ID yet.
        $permittedUserIds = collect();

        if ($this->can !== false) {
            // Get the users that have that permission directly
            $permittedUserIds = DB::table(Table::USERPERMISSIONS_USERS)
                ->whereIn('permissionId', Arr::wrap($this->can))
                ->pluck('userId');

            // Get the users that have that permission via a user group
            $permittedUserIdsViaGroups = DB::table(Table::USERGROUPS_USERS, 'g_u')
                ->select('g_u.userId')
                ->join(new Alias(Table::USERPERMISSIONS_USERGROUPS, 'p_g'), 'p_g.groupId', '=', 'g_u.groupId')
                ->whereIn('p_g.permissionId', Arr::wrap($this->can))
                ->pluck('userId');

            $permittedUserIds = $permittedUserIds->merge($permittedUserIdsViaGroups)->unique();
        }

        $userQuery->when(
            $permittedUserIds->isEmpty(),
            fn (UserQuery $userQuery) => $userQuery->subQuery->where('users.admin', true),
            fn (UserQuery $userQuery) => $userQuery->subQuery->where(function (Builder $query) use ($permittedUserIds) {
                $query->where('users.admin', true)
                    ->orWhereIn('users.id', $permittedUserIds);
            }),
        );
    }
}
