<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Assets;

use craft\helpers\Assets as AssetsHelper;
use CraftCms\Cms\Http\RespondsWithFlash;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class IconController
{
    use RespondsWithFlash;

    public function __invoke(Request $request, ?string $extension = null): Response
    {
        $svg = AssetsHelper::iconSvg($request->input('extension', $extension));

        // iconSvg() may return a file path when the icon is already cached on disk
        if (is_file($svg)) {
            return response()->file($svg, [
                'Content-Type' => 'image/svg+xml',
                'Cache-Control' => 'public, max-age=31536000',
            ]);
        }

        return response($svg, headers: [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
}
