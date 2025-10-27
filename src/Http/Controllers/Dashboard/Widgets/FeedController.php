<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Dashboard\Widgets;

use CraftCms\Cms\Config\GeneralConfig;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class FeedController
{
    public function cacheData(Request $request, Repository $cache, GeneralConfig $generalConfig): JsonResponse
    {
        $request->validate([
            'url' => ['required', 'url'],
            'data' => ['required'],
        ]);

        $cache->put("feed:{$request->input('url')}", $request->input('data'), $generalConfig->cacheDuration);

        return new JsonResponse;
    }
}
