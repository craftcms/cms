<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Auth\Auth;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Support\Facades\InternalAssets;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Http\Request;

readonly class Enforce2fa
{
    public function __construct(
        private GeneralConfig $generalConfig,
        private Auth $auth,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if ($this->generalConfig->disable2fa) {
            return $next($request);
        }

        if (! $user = $request->user('craft')) {
            return $next($request);
        }

        /** @var User $user */
        if ($this->auth->is2faRequired($user) && ! $this->auth->hasActiveMethod($user)) {
            InternalAssets::register('auth-method-setup');
            TemplateMode::set(TemplateMode::Cp);

            return response()
                ->view('_special/setup-2fa')
                ->setNoCacheHeaders();
        }

        return $next($request);
    }
}
