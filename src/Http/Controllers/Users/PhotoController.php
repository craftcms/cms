<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use Closure;
use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\View\TemplateResolver;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

readonly class PhotoController
{
    use AuthorizesRequests;
    use RespondsWithFlash;

    public function __construct(
        private Users $users,
    ) {}

    public function renderInput(Request $request): JsonResponse
    {
        $user = $this->authorizeUserPhotoTarget($request);

        return $this->renderPhotoTemplate($request, $user);
    }

    public function upload(Request $request): Response
    {
        $user = $this->authorizeUserPhotoTarget($request);

        $request->validate([
            'photo' => [
                'nullable',
                'file',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value instanceof UploadedFile && $value->getSize() > AssetsHelper::getMaxUploadSize()) {
                        $fail(t('The uploaded file exceeds the maximum allowed size.'));
                    }
                },
            ],
        ]);

        if (! $request->hasFile('photo')) {
            return new JsonResponse;
        }

        try {
            $uploadedFile = $request->file('photo');

            // Move to our own temp location
            $fileLocation = AssetsHelper::tempFilePath($uploadedFile->extension());
            $file = $uploadedFile->move(dirname($fileLocation), basename($fileLocation));
            $this->users->saveUserPhoto($fileLocation, $user, $uploadedFile->getClientOriginalName(), $file->getMimeType());

            return $this->renderPhotoTemplate($request, $user);
        } catch (Throwable $exception) {
            if (isset($fileLocation) && file_exists($fileLocation)) {
                File::delete($fileLocation);
            }

            Log::error('There was an error uploading the photo: '.$exception->getMessage());

            return $this->asFailure(t('There was an error uploading your photo: {error}', [
                'error' => $exception->getMessage(),
            ]));
        }

    }

    public function destroy(Request $request, Elements $elements): JsonResponse
    {
        $user = $this->authorizeUserPhotoTarget($request);

        if ($user->photoId) {
            $elements->deleteElementById($user->photoId, Asset::class);
        }

        $user->photoId = null;
        $elements->saveElement($user, false);

        return $this->renderPhotoTemplate($request, $user);
    }

    private function authorizeUserPhotoTarget(Request $request): User
    {
        $request->validate([
            'userId' => ['required', 'integer'],
        ]);

        $userId = $request->integer('userId');
        $user = $this->users->getUserById($userId);

        abort_if(! $user, 400, "Invalid user ID: {$userId}");

        $this->authorize('save', $user);

        return $user;
    }

    private function renderPhotoTemplate(Request $request, User $user): JsonResponse
    {
        $templateMode = TemplateMode::get();
        if (TemplateMode::is(TemplateMode::Site) && ! app(TemplateResolver::class)->exists('users/_photo.twig')) {
            $templateMode = TemplateMode::Cp;
        }

        $data = [
            'html' => template('users/_photo', [
                'user' => $user,
            ], templateMode: $templateMode),
            'photoId' => $user->photoId,
        ];

        if ($user->getIsCurrent() && $request->isCpRequest()) {
            $data['headerPhotoHtml'] = template('_layouts/components/header-photo');
        }

        return new JsonResponse($data);
    }
}
