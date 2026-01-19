<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use craft\helpers\Cp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class IconController
{
    public function svg(Request $request): JsonResponse
    {
        $request->validate([
            'icon' => ['required', 'string'],
        ]);

        return new JsonResponse([
            'iconSvg' => Cp::iconSvg($request->string('icon')->toString()),
        ]);
    }
}
