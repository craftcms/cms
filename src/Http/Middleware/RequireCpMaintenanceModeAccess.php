<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as LaravelPreventRequestsDuringMaintenance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

use function CraftCms\Cms\t;

readonly class RequireCpMaintenanceModeAccess
{
    public function __construct(
        private LaravelPreventRequestsDuringMaintenance $maintenanceMode,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if (! app()->isDownForMaintenance() || Gate::check('accessCpWhenSystemIsOff')) {
            return $next($request);
        }

        if (Auth::guest()) {
            return $this->maintenanceMode->handle($request, $next);
        }

        throw new ServiceUnavailableHttpException(
            retryAfter: app()->maintenanceMode()->data()['retry'] ?? null,
            message: t('Your account doesn’t have permission to access the control panel when maintenance mode is enabled.'),
        );
    }
}
