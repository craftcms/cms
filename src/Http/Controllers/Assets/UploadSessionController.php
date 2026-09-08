<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Assets;

use CraftCms\Cms\Asset\AssetUploads;
use CraftCms\Cms\Asset\Data\UploadSessionData;
use CraftCms\Cms\Http\Requests\UploadRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class UploadSessionController
{
    public function __construct(private AssetUploads $uploads) {}

    public function store(UploadRequest $request): JsonResponse
    {
        $session = $this->uploads->start($request);
        $prefix = $request->isCpRequest() ? 'craft.actions.craft.cp.uploads' : 'craft.actions.craft.uploads';

        return new JsonResponse(new UploadSessionData(
            id: $session->id,
            chunkSize: $session->chunkSize,
            partCount: $session->partCount(),
            urls: [
                'part' => route("$prefix.part", ['upload' => $session->id]),
                'complete' => route("$prefix.complete", ['upload' => $session->id]),
                'cancel' => route("$prefix.destroy", ['upload' => $session->id]),
            ],
        )->toArray(), 201);
    }

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
        $result = $this->uploads->complete($request, $upload);

        return new JsonResponse($result->payload(), $result->status);
    }

    public function destroy(Request $request, string $upload): Response
    {
        $this->uploads->cancel($request, $upload);

        return response()->noContent();
    }
}
