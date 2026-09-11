<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Filesystem\Data\UploadSessionData;
use CraftCms\Cms\Filesystem\Uploads;
use CraftCms\Cms\Http\Requests\UploadRequest;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\UserPhotoUploads;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

readonly class PhotoController
{
    public function __construct(
        private UserPhotoUploads $photos,
    ) {}

    public function renderInput(Request $request): JsonResponse
    {
        $user = $this->authorizeUserPhotoTarget($request);

        return $this->photos->renderInput($request, $user);
    }

    public function upload(UploadRequest $request, Uploads $uploads): JsonResponse
    {
        $data = $request->validated();
        $session = $uploads->start(
            $request,
            UserPhotoUploads::class,
            $data['filename'],
            (int) $data['size'],
            $request->only('userId'),
        );

        return new JsonResponse(UploadSessionData::fromSession($session)->toArray(), 201);
    }

    public function destroy(Request $request, Elements $elements): JsonResponse
    {
        $user = $this->authorizeUserPhotoTarget($request);

        if ($user->photoId) {
            $elements->deleteElementById($user->photoId, Asset::class);
        }

        $user->photoId = null;
        $elements->saveElement($user, false);

        return $this->photos->renderInput($request, $user);
    }

    private function authorizeUserPhotoTarget(Request $request): User
    {
        $request->validate([
            'userId' => ['required', 'integer'],
        ]);

        return $this->photos->user($request->integer('userId'));
    }
}
