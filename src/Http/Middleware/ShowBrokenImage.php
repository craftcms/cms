<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\File;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @since 6.0.0
 */
class ShowBrokenImage
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if (! $response instanceof Response) {
            return $response;
        }

        $statusCode = $response->getStatusCode();

        if ($statusCode !== 404) {
            return $response;
        }

        if (! Cms::config()->images->brokenPath) {
            return $response;
        }

        $acceptable = $request->getAcceptableContentTypes();

        if (! isset($acceptable[0])) {
            return $response;
        }

        // If request does not want an image
        if (! Str::contains(strtolower($acceptable[0]), ['image/'])) {
            return $response;
        }

        $brokenPath = Cms::config()->images->brokenPath;
        $imagePath = Aliases::get($brokenPath);

        if (! is_file($imagePath)) {
            throw new RuntimeException("Invalid broken image path: $brokenPath");
        }

        return response(File::get($imagePath), $statusCode, [
            'Content-Type' => File::mimeType($imagePath),
        ]);
    }
}
