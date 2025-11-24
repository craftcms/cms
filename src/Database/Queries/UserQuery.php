<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries;

use Closure;
use craft\elements\User;
use CraftCms\Cms\Database\Queries\Concerns\User\QueriesAffiliatedSite;
use CraftCms\Cms\Database\Queries\Concerns\User\QueriesAssetUploaders;
use CraftCms\Cms\Database\Queries\Concerns\User\QueriesAuthors;
use CraftCms\Cms\Database\Queries\Concerns\User\QueriesRolesAndPermissions;
use CraftCms\Cms\Database\Queries\Concerns\User\QueriesUserGroups;
use CraftCms\Cms\Database\Queries\Concerns\User\QueriesUserProperties;
use CraftCms\Cms\Database\Table;
use Illuminate\Database\Query\Builder;

final class UserQuery extends ElementQuery
{
    use QueriesAffiliatedSite;
    use QueriesAssetUploaders;
    use QueriesAuthors;
    use QueriesRolesAndPermissions;
    use QueriesUserGroups;
    use QueriesUserProperties;

    public const string STATUS_CREDENTIALED = 'credentialed';

    /**
     * {@inheritdoc}
     */
    protected array $defaultOrderBy = [
        'users.username' => SORT_ASC,
    ];

    public function __construct(array $config = [])
    {
        parent::__construct(\CraftCms\Cms\Element\Elements\User::class, $config);

        $this->joinElementTable(Table::USERS);

        $this->query->addSelect([
            'users.photoId as photoId',
            'users.pending as pending',
            'users.locked as locked',
            'users.suspended as suspended',
            'users.admin as admin',
            'users.username as username',
            'users.firstName as firstName',
            'users.lastName as lastName',
            'users.email as email',
            'users.unverifiedEmail as unverifiedEmail',
            'users.lastLoginDate as lastLo2ginDate',
            'users.lockoutDate as lockoutDate',
            'users.hasDashboard as hasDashboard',
            'users.affiliatedSiteId as affiliatedSiteId',
            'users.active as active',
            'users.fullName as fullName',
        ]);
    }

    /**
     * Narrows the query results based on the users’ statuses.
     *
     * Possible values include:
     *
     * | Value | Fetches users…
     * | - | -
     * | `'inactive'` | with inactive accounts.
     * | `'active'` | with active accounts.
     * | `'pending'` | with accounts that are still pending activation.
     * | `'credentialed'` | with either active or pending accounts.
     * | `'suspended'` | with suspended accounts.
     * | `'locked'` | with locked accounts (regardless of whether they’re active or suspended).
     * | `['active', 'suspended']` | with active or suspended accounts.
     * | `['not', 'active', 'suspended']` | without active or suspended accounts.
     *
     * ---
     *
     * ```twig
     * {# Fetch active and locked users #}
     * {% set {elements-var} = {twig-method}
     *   .status(['active', 'locked'])
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch active and locked users
     * ${elements-var} = {element-class}::find()
     *     ->status(['active', 'locked'])
     *     ->all();
     * ```
     */
    public function status(array|string|null $value): self
    {
        /** @var static */
        return parent::status($value);
    }

    /**
     * {@inheritdoc}
     */
    protected function statusCondition(string $status): Closure
    {
        return match ($status) {
            User::STATUS_INACTIVE => fn (Builder $query) => $query
                ->where('users.active', false)
                ->where('users.pending', false),
            User::STATUS_ACTIVE => fn (Builder $query) => $query
                ->where('users.active', true)
                ->where('users.suspended', false),
            User::STATUS_PENDING => fn (Builder $query) => $query->where('users.pending', true),
            self::STATUS_CREDENTIALED => fn (Builder $query) => $query->where(function (Builder $query) {
                $query
                    ->where('users.active', true)
                    ->orWhere('users.pending', true);
            }),
            User::STATUS_SUSPENDED => fn (Builder $query) => $query->where('users.suspended', true),
            User::STATUS_LOCKED => fn (Builder $query) => $query->where('users.locked', true),
            default => parent::statusCondition($status),
        };
    }
}
