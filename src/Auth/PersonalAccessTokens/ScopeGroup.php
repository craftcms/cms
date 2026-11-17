<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\PersonalAccessTokens;

use Closure;
use CraftCms\Cms\User\Elements\User;

/**
 * A set of Passport scopes that users can grant to the personal access tokens they create on their Tokens screen,
 * such as the scopes for one API.
 *
 * @since 6.0.0
 */
class ScopeGroup
{
    public function __construct(
        public string $handle,
        /** The API name, shown when more than one group is available. */
        public string $label,
        /** @var array<string, string> Scope IDs and their descriptions. */
        public array $scopes,
        /**
         * Returns the scope IDs, out of those given, that a user may grant. All of them by default.
         *
         * @var (Closure(User, string[]): string[])|null
         */
        public ?Closure $userScopes = null,
    ) {}

    /**
     * The scope IDs the user may grant to a token.
     *
     * @return string[]
     */
    public function scopesFor(User $user): array
    {
        $scopes = array_keys($this->scopes);

        if ($this->userScopes) {
            $scopes = array_values(array_intersect($scopes, ($this->userScopes)($user, $scopes)));
        }

        return $scopes;
    }
}
