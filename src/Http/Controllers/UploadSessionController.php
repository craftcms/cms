<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Filesystem\Uploads;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class UploadSessionController
{
    public function __construct(private Uploads $uploads) {}

    public function transfer(Request $request, string $upload): Response
    {
        return $this->uploads->transfer($request, $upload);
    }

    public function status(Request $request, string $upload): JsonResponse
    {
        return new JsonResponse($this->uploads->status($request, $upload));
    }

    public function complete(Request $request, string $upload): JsonResponse
    {
        return $this->uploads->complete($request, $upload);
    }

    public function destroy(Request $request, string $upload): Response
    {
        $this->uploads->cancel($request, $upload);

        return response()->noContent();
    }
}
