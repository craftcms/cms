<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Auth\AuthMethods;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Http\Request;

readonly class Enforce2fa
{
    public function __construct(
        private GeneralConfig $generalConfig,
        private AuthMethods $auth,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if ($this->generalConfig->disable2fa) {
            return $next($request);
        }

        if (! $user = $request->craftUser()) {
            return $next($request);
        }

        /** @var User $user */
        if ($this->auth->is2faRequired($user) && ! $this->auth->hasActiveMethod($user)) {
            TemplateMode::set(TemplateMode::Cp);

            return response()
                ->view('_special/setup-2fa')
                ->setNoCacheHeaders();
        }

        return $next($request);
    }
}
