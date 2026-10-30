<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Enums;

use Laravel\Passport\Contracts\ScopeAuthorizable;

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
