<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Http;

use Closure;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use CraftCms\Yii2Adapter\View\LegacyAssets\CpCompatAsset;
use Illuminate\Http\Request;

/**
 * Queues the deprecated legacy CP jQuery plugin shims ({@see CpCompatAsset})
 * for CP requests, mirroring how core registers the CP bundle from
 * `HandleInertiaRequests`. Runs per-request so it stays Octane-safe.
 *
 * @internal
 */
class RegisterLegacyCompatAssets
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->isCpRequest()) {
            app(InternalAssetRegistry::class)->register(CpCompatAsset::class);
        }

        return $next($request);
    }
}
