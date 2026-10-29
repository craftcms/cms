<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Auth\PersonalAccessTokens\PersonalAccessTokens;
use CraftCms\Cms\Auth\PersonalAccessTokens\ScopeGroup;
use CraftCms\Cms\User\Elements\User;

/**
 * @since 6.0.0
 */
class UserTokensViewModel extends ViewModel
{
    /**
     * The scope groups the user can create tokens for, with the scopes they may grant.
     *
     * @var list<array{handle: string, label: string, scopes: list<array{value: string, label: string}>}>
     */
    public array $groups;

    /**
     * The user’s personal access tokens and authorized apps.
     *
     * @var list<array{
     *     group: string,
     *     clientId: string,
     *     tokenId: string|null,
     *     name: string,
     *     scopes: string[],
     *     expiresAt: string,
     *     personal: bool,
     * }>
     */
    public array $grants;

    public function __construct(User $user, PersonalAccessTokens $tokens)
    {
        $groups = $tokens->scopeGroupsFor($user);

        $this->groups = $groups
            ->map(fn (ScopeGroup $group): array => [
                'handle' => $group->handle,
                'label' => $group->label,
                'scopes' => array_map(fn (string $scope): array => [
                    'value' => $scope,
                    'label' => $group->scopes[$scope],
                ], $group->scopesFor($user)),
            ])
            ->values()
            ->all();

        $this->grants = array_values(array_map(fn (array $grant): array => [
            ...$grant,
            'expiresAt' => $grant['expiresAt']->toIso8601String(),
        ], array_filter($tokens->grants($user), fn (array $grant): bool => $groups->has($grant['group']))));
    }
}
