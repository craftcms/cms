<?php

namespace CraftCms\Cms\Http\Controllers\Dashboard\Widgets;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedController
{
    public function cacheData(Request $request, Repository $cache): JsonResponse
    {
        $request->validate([
            'url' => ['required', 'url'],
            'data' => ['required'],
        ]);

        $cache->put("feed:{$request->get('url')}", $request->get('data'), \Craft::$app->getConfig()->getGeneral()->cacheDuration);

        return new JsonResponse;
    }
}
