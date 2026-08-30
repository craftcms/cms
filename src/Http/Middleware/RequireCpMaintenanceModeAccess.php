<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as LaravelPreventRequestsDuringMaintenance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Override;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

use function CraftCms\Cms\t;

class RequireCpMaintenanceModeAccess extends LaravelPreventRequestsDuringMaintenance
{
    /**
     * @param  Request  $request
     */
    #[Override]
    public function handle($request, Closure $next): mixed
    {
        if ($this->inExceptArray($request)) {
            return $next($request);
        }

        if (! app()->isDownForMaintenance() || Gate::check('accessCpWhenSystemIsOff')) {
            return $next($request);
        }

        if (Auth::guest()) {
            return parent::handle($request, $next);
        }

        throw new ServiceUnavailableHttpException(
            retryAfter: app()->maintenanceMode()->data()['retry'] ?? null,
            message: t('Your account doesn’t have permission to access the control panel when maintenance mode is enabled.'),
        );
    }
}
