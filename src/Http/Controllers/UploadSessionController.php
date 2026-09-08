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

    public function part(Request $request, string $upload): JsonResponse
    {
        $data = $request->validate(['part' => ['required', 'integer', 'min:1']]);

        return new JsonResponse($this->uploads->part($request, $upload, (int) $data['part']));
    }

    public function chunk(Request $request, string $upload, int $part): Response
    {
        $this->uploads->receive($request, $upload, $part);

        return response()->noContent();
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
