<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Assets;

use CraftCms\Cms\Asset\AssetUploads;
use CraftCms\Cms\Filesystem\Data\UploadSessionData;
use CraftCms\Cms\Filesystem\Uploads;
use CraftCms\Cms\Http\Requests\AssetUploadRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

readonly class UploadSessionController
{
    public function __construct(private Uploads $uploads) {}

    public function store(AssetUploadRequest $request): JsonResponse
    {
        $data = $request->validated();
        $session = $this->uploads->start(
            $request,
            AssetUploads::class,
            $data['filename'],
            (int) $data['size'],
            Arr::except($data, ['filename', 'size']),
        );

        return new JsonResponse(UploadSessionData::fromSession($session)->toArray(), 201);
    }
}
