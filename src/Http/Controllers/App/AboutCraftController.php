<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\App;

use CraftCms\Cms\Cp\AboutCraft;
use Illuminate\Http\JsonResponse;

/**
 * Returns what the command palette’s “About Craft CMS” view shows.
 *
 * @since 6.0.0
 */
class AboutCraftController
{
    public function __invoke(AboutCraft $about): JsonResponse
    {
        return new JsonResponse($about->toArray());
    }
}
