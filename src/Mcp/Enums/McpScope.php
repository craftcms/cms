<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Enums;

use CraftCms\Cms\User\Contracts\CraftUser;
use Laravel\Passport\Contracts\ScopeAuthorizable;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

use function CraftCms\Cms\t;

/**
 * OAuth scopes for the authenticated MCP endpoint, ordered from narrowest to broadest.
 *
 * Each scope includes the ones before it. Scopes only narrow what the connected user's
 * Craft permissions already allow.
 *
 * @since 6.0.0
 */
enum McpScope: string
{
    case Read = 'mcp:read';
    case Write = 'mcp:write';
    case Admin = 'mcp:admin';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * The scopes a user may choose between when approving a client that requested `$requested`.
     *
     * @param  list<string>  $requested
     * @return list<self>
     */
    public static function choices(array $requested, CraftUser $user): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $scope): bool => in_array($scope->value, $requested, true) && ($scope !== self::Admin || $user->isAdmin()),
        ));
    }

    /**
     * The broadest scope in `$values`.
     *
     * @param  list<string>  $values
     */
    public static function highestIn(array $values): ?self
    {
        return collect(self::cases())->last(fn (self $scope): bool => in_array($scope->value, $values, true));
    }

    /**
     * Removes requested scopes that `$level` does not grant, keeping scopes that aren’t MCP scopes.
     *
     * @param  list<ScopeEntityInterface>  $scopes
     * @return list<ScopeEntityInterface>
     */
    public static function narrow(array $scopes, ?self $level): array
    {
        $granted = $level?->grantedValues() ?? [];

        return array_values(array_filter(
            $scopes,
            fn (ScopeEntityInterface $scope): bool => self::tryFrom($scope->getIdentifier()) === null
                || in_array($scope->getIdentifier(), $granted, true),
        ));
    }

    public function label(): string
    {
        return match ($this) {
            self::Read => t('Read only'),
            self::Write => t('Read and write'),
            self::Admin => t('Full access'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Read => t('View content you have access to.'),
            self::Write => t('View, create, edit, and delete content you have access to.'),
            self::Admin => t('Everything you can do, including admin-only tools such as changing settings.'),
        };
    }

    /**
     * The scope values that satisfy this scope.
     *
     * @return list<string>
     */
    public function satisfyingValues(): array
    {
        return array_slice(self::values(), array_search($this, self::cases(), true));
    }

    /**
     * The scope values granted by choosing this scope.
     *
     * @return list<string>
     */
    public function grantedValues(): array
    {
        return array_slice(self::values(), 0, array_search($this, self::cases(), true) + 1);
    }

    public function isGrantedTo(ScopeAuthorizable $token): bool
    {
        return array_any($this->satisfyingValues(), $token->can(...));
    }
}
