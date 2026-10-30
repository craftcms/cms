<?php

namespace craft\oauth;

use Closure;
use craft\elements\User;

/**
 * A set of OAuth scopes that users can grant to the personal access tokens they create on their Tokens screen,
 * such as the scopes for one API.
 *
 * The scopes must be defined by a registered OAuth [[Resource]].
 *
 * @since 5.12.0
 */
class ScopeGroup
{
    /**
     * @param string $handle
     * @param string $label The API name, shown when more than one group is available.
     * @param array<string,string> $scopes Scope IDs and their descriptions.
     * @param Closure|null $userScopes Returns the scope IDs, out of those given, that a user may grant. All of them by
     * default. Signature: `function(User $user, string[] $scopes): string[]`
     */
    public function __construct(
        public string $handle,
        public string $label,
        public array $scopes,
        public ?Closure $userScopes = null,
    ) {
    }

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
