<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Http;

use Closure;
use CraftCms\Cms\Http\Controllers\Dashboard\DashboardController;
use CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController;
use CraftCms\Cms\View\LegacyAssets\AxiosAsset;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use CraftCms\Yii2Adapter\View\LegacyAssets\CpCompatAsset;
use CraftCms\Yii2Adapter\View\LegacyAssets\DashboardCompatAsset;
use Illuminate\Http\Request;

/**
 * Queues the deprecated legacy CP jQuery plugin shims ({@see CpCompatAsset})
 * and the global `axios` ({@see AxiosAsset}) for CP requests, mirroring how
 * core registers the CP bundle from `HandleInertiaRequests`. Core no longer
 * loads axios, but plugin code written against Craft 5 expects it. Runs
 * per-request so it stays Octane-safe.
 *
 * @internal
 */
class RegisterLegacyCompatAssets
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->isCpRequest()) {
            app(InternalAssetRegistry::class)->register(AxiosAsset::class);
            app(InternalAssetRegistry::class)->register(CpCompatAsset::class);
        }

        if (in_array($request->route()?->getControllerClass(), [DashboardController::class, WidgetsController::class], true)) {
            app(InternalAssetRegistry::class)->register(DashboardCompatAsset::class);
        }

        return $next($request);
    }
}
