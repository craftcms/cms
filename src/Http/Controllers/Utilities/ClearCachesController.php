<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Utilities;

use craft\helpers\FileHelper;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utilities\ClearCaches;
use CraftCms\DependencyAwareCache\Dependency\TagDependency;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

final readonly class ClearCachesController
{
    public function __construct(Utilities $utilitiesService)
    {
        if (! $utilitiesService->checkAuthorization(ClearCaches::class)) {
            abort(403, 'User is not authorized to perform this action.');
        }
    }

    public function clearCaches(Request $request, Application $app): JsonResponse
    {
        $caches = $request->validate([
            'caches' => ['required'],
        ])['caches'];

        foreach (ClearCaches::cacheOptions() as $cacheOption) {
            if (is_array($caches) && ! in_array($cacheOption['key'], $caches, true)) {
                continue;
            }

            $action = $cacheOption['action'];

            if (is_string($action)) {
                try {
                    FileHelper::clearDirectory($action);
                } catch (InvalidArgumentException) {
                    // the directory doesn't exist
                } catch (Throwable $e) {
                    Log::warning("Could not clear the directory $action: ".$e->getMessage(), [__METHOD__]);
                }
            } elseif (isset($cacheOption['params'])) {
                $app->call($action, $cacheOption['params']);
            } else {
                $action();
            }
        }

        return new JsonResponse;
    }

    public function invalidateTags(Request $request): JsonResponse
    {
        $tags = $request->validate([
            'tags' => ['required', 'array'],
            'tags.*' => ['string'],
        ])['tags'];

        foreach ($tags as $tag) {
            TagDependency::invalidate($tag);
        }

        return new JsonResponse;
    }
}
