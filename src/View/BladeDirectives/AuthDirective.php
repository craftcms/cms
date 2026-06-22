<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\BladeDirectives;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL as LaravelUrl;
use Illuminate\View\Compilers\BladeCompiler;

use function CraftCms\Cms\currentUser;

class AuthDirective
{
    public static function register(BladeCompiler $blade): void
    {
        $blade->directive('craftRequireLogin', fn (string $expression = '') => sprintf('<?php %s::requireLogin(); ?>', self::class));
        $blade->directive('craftRequireGuest', fn (string $expression = '') => sprintf('<?php %s::requireGuest(); ?>', self::class));
        $blade->directive('craftRequireAdmin', fn (string $expression) => sprintf('<?php %s::requireAdmin(%s); ?>', self::class, $expression));
        $blade->directive('craftRequirePermission', fn (string $expression) => sprintf('<?php \%s::authorize(%s); ?>', Gate::class, $expression));
        $blade->directive('craftRequireEdition', fn (string $expression) => sprintf('<?php \%s::require(%s); ?>', Edition::class, $expression));
    }

    public static function requireLogin(): void
    {
        if (Auth::guest()) {
            throw new AuthenticationException('Unauthenticated.');
        }
    }

    public static function requireGuest(): void
    {
        if (Auth::check()) {
            redirect(LaravelUrl::returnUrl())->throwResponse();
        }
    }

    public static function requireAdmin(bool $requireAdminChanges = true): void
    {
        if (! $user = currentUser()) {
            throw new AuthenticationException('Unauthenticated.');
        }

        if (! $user->isAdmin()) {
            abort(403, 'User is not permitted to perform this action.');
        }

        if ($requireAdminChanges && ! Cms::config()->allowAdminChanges) {
            abort(403, 'Administrative changes are disallowed in this environment.');
        }
    }
}
