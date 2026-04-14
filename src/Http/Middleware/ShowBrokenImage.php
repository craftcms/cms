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

        if (! Cms::config()->brokenImagePath) {
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

        $generalConfig = Cms::config();
        $imagePath = Aliases::get($generalConfig->brokenImagePath);

        if (! is_file($imagePath)) {
            throw new RuntimeException("Invalid broken image path: $generalConfig->brokenImagePath");
        }

        return response(File::get($imagePath), $statusCode, [
            'Content-Type' => File::mimeType($imagePath),
        ]);
    }
}
