<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\InstallController;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\Request;

use function CraftCms\Cms\t;

readonly class EnsureInstalled
{
    public function handle(Request $request, Closure $next): mixed
    {
        $isInstallRequest = $request->route()->getControllerClass() === InstallController::class;

        if (Cms::isInstalled()) {
            if ($isInstallRequest) {
                return redirect(Url::cpUrl('dashboard'));
            }

            return $next($request);
        }

        if ($isInstallRequest) {
            return $next($request);
        }

        if (! $request->isCpRequest()) {
            abort(503);
        }

        if (! app()->hasDebugModeEnabled()) {
            abort(503, t('Craft isn’t installed yet.'));
        }

        return redirect()->action([InstallController::class, 'index']);
    }
}
