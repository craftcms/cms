<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth;

use CraftCms\Cms\Auth\Methods\AuthMethodInterface;
use CraftCms\Cms\Auth\Methods\RecoveryCodes;
use CraftCms\Cms\Auth\Methods\TOTP;
use CraftCms\Cms\Component\TypeRegistry;
use Illuminate\Container\Attributes\Singleton;

/**
 * Internal type catalog backing {@see AuthMethods} registration.
 *
 * @internal
 *
 * @extends TypeRegistry<AuthMethodInterface>
 */
#[Singleton]
class AuthMethodCatalog extends TypeRegistry
{
    protected const string CONTRACT = AuthMethodInterface::class;

    protected const array DEFAULT_TYPES = [
        TOTP::class,
        RecoveryCodes::class,
    ];

    /** @param class-string<AuthMethodInterface> $type */
    #[\Override]
    protected function identity(string $type): string
    {
        return $type::handle();
    }
}
