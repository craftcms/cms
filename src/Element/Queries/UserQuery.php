<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries;

use Closure;
use CraftCms\Cms\Database\Expressions\OrderByPlaceholderExpression;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Queries\Concerns\FormatsResults;
use CraftCms\Cms\Element\Queries\Concerns\User\QueriesAffiliatedSite;
use CraftCms\Cms\Element\Queries\Concerns\User\QueriesAssetUploaders;
use CraftCms\Cms\Element\Queries\Concerns\User\QueriesAuthors;
use CraftCms\Cms\Element\Queries\Concerns\User\QueriesRolesAndPermissions;
use CraftCms\Cms\Element\Queries\Concerns\User\QueriesUserGroups;
use CraftCms\Cms\Element\Queries\Concerns\User\QueriesUserProperties;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Database\Query\Builder;
use Override;

/**
 * @extends ElementQuery<User>
 */
class UserQuery extends ElementQuery
{
    use QueriesAffiliatedSite;
    use QueriesAssetUploaders;
    use QueriesAuthors;
    use QueriesRolesAndPermissions;
    use QueriesUserGroups;
    use QueriesUserProperties;

    public const string STATUS_CREDENTIALED = 'credentialed';

    #[Override]
    protected array $defaultOrderBy = [
        'users.username' => SORT_ASC,
        'users.active' => SORT_DESC,
        'users.pending' => SORT_DESC,
    ];

    public function __construct(array $config = [])
    {
        parent::__construct(User::class, $config);

        $this->joinElementTable(Table::USERS);

        $this->query->addSelect([
            'users.photoId',
            'users.pending',
            'users.locked',
            'users.suspended',
            'users.admin',
            'users.username',
            'users.firstName',
            'users.lastName',
            'users.email',
            'users.unverifiedEmail',
            'users.lastLoginDate',
            'users.lockoutDate',
            'users.hasDashboard',
            'users.affiliatedSiteId',
            'users.active',
            'users.fullName',
            'users.rememberToken',
        ]);

        $this->beforeQuery(function (self $userQuery) {
            $orders = $userQuery->query->orders;

            if (is_null($orders)) {
                return;
            }

            $orders = array_filter(
                array: $orders,
                callback: fn ($order) => ! $order['column'] instanceof OrderByPlaceholderExpression,
            );

            // Order by was not set so we can fall back to the applyDefaultOrder logic in FormatsResults
            if (empty($orders)) {
                return;
            }

            $orders = array_merge($orders, [
                [
                    'column' => 'users.active',
                    'direction' => 'desc',
                ],
                [
                    'column' => 'users.pending',
                    'direction' => 'desc',
                ],
            ]);

            // If there's a custom orderBy, make sure we're showing active, non-pending accounts first
            $userQuery->query->orders = $orders;
        });
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
    #[Override]
    public function status(array|string|null $value): static
    {
        /** @var static */
        return parent::status($value);
    }

    #[Override]
    protected function statusCondition(string $status): Closure
    {
        return match ($status) {
            User::STATUS_INACTIVE => fn (Builder $query) => $query
                ->whereBool('users.active', false)
                ->whereBool('users.pending', false),
            User::STATUS_ACTIVE => fn (Builder $query) => $query
                ->whereBool('users.active', true)
                ->whereBool('users.suspended', false),
            User::STATUS_PENDING => fn (Builder $query) => $query->whereBool('users.pending', true),
            self::STATUS_CREDENTIALED => fn (Builder $query) => $query->where(function (Builder $query) {
                $query
                    ->whereBool('users.active', true)
                    ->orWhereBool('users.pending', true);
            }),
            User::STATUS_SUSPENDED => fn (Builder $query) => $query->whereBool('users.suspended', true),
            User::STATUS_LOCKED => fn (Builder $query) => $query->whereBool('users.locked', true),
            default => parent::statusCondition($status),
        };
    }
}
