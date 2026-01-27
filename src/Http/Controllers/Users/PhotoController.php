<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use Craft;
use craft\helpers\Assets;
use craft\web\View;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function CraftCms\Cms\t;

final readonly class PhotoController
{
    use AuthorizesRequests;
    use RespondsWithFlash;

    public function __construct(
        private Users $users,
    ) {}

    public function renderInput(Request $request): JsonResponse
    {
        $request->validate([
            'userId' => ['required', 'integer'],
        ]);

        $user = $this->users->getUserById($request->integer('userId'));

        abort_if(! $user, 400, 'Invalid user ID: '.$request->integer('userId'));

        return $this->renderPhotoTemplate($request, $user);
    }

    public function upload(Request $request): Response
    {
        $request->validate([
            'userId' => ['required', 'integer'],
        ]);

        if ($request->integer('userId') !== $request->user()->id) {
            $this->authorize('editUsers');
        }

        if (! $request->hasFile('photo')) {
            return new JsonResponse;
        }

        try {
            $uploadedFile = $request->file('photo');

            $user = $this->users->getUserById($request->integer('userId'));

            abort_if(! $user, 400, 'Invalid user ID: '.$request->integer('userId'));

            // Move to our own temp location
            $fileLocation = Assets::tempFilePath($uploadedFile->extension());
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

    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'userId' => ['required', 'integer'],
        ]);

        $user = $this->users->getUserById($request->integer('userId'));

        abort_if(! $user, 400, 'Invalid user ID: '.$request->integer('userId'));

        if ($user->photoId) {
            Craft::$app->getElements()->deleteElementById($user->photoId, Asset::class);
        }

        $user->photoId = null;
        Craft::$app->getElements()->saveElement($user, false);

        return $this->renderPhotoTemplate($request, $user);
    }

    private function renderPhotoTemplate(Request $request, User $user): JsonResponse
    {
        $view = Craft::$app->getView();

        $templateMode = $view->getTemplateMode();
        if ($templateMode === View::TEMPLATE_MODE_SITE && ! $view->doesTemplateExist('users/_photo.twig')) {
            $templateMode = View::TEMPLATE_MODE_CP;
        }

        $data = [
            'html' => $view->renderTemplate('users/_photo.twig', [
                'user' => $user,
            ], $templateMode),
            'photoId' => $user->photoId,
        ];

        if ($user->getIsCurrent() && $request->isCpRequest()) {
            $data['headerPhotoHtml'] = $view->renderTemplate('_layouts/components/header-photo.twig');
        }

        return new JsonResponse($data);
    }
}
