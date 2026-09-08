<?php

declare(strict_types=1);

namespace CraftCms\Cms\User;

use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\Exceptions\ImageException;
use CraftCms\Cms\Filesystem\Contracts\UploadHandler;
use CraftCms\Cms\Filesystem\Data\UploadedFile;
use CraftCms\Cms\Image\ImageHelper;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\View\TemplateResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

readonly class UserPhotoUploads implements UploadHandler
{
    public function __construct(private Users $users) {}

    public function authorize(Request $request, array $parameters, string $filename, int $size): array
    {
        abort_if($request->user() === null, 401);
        $data = Validator::validate($parameters, ['userId' => ['required', 'integer', 'min:1']]);
        $user = $this->user((int) $data['userId']);

        abort_unless(ImageHelper::canManipulateAsImage(pathinfo($filename, PATHINFO_EXTENSION)), 422, t('User photo must be an image that Craft can manipulate.'));
        abort_if($size > AssetsHelper::getMaxAssetUploadSize(), 422, t('The uploaded file exceeds the maximum allowed size.'));

        return ['userId' => $user->id];
    }

    public function complete(Request $request, array $parameters, UploadedFile $file): JsonResponse
    {
        $user = $this->user((int) $parameters['userId']);
        try {
            $this->users->saveUserPhoto($file->localPath(), $user, $file->filename, $file->mimeType());
        } catch (ImageException $exception) {
            throw ValidationException::withMessages(['photo' => $exception->getMessage()]);
        }

        return $this->renderInput($request, $user);
    }

    public function user(int $userId): User
    {
        $user = $this->users->getUserById($userId);
        abort_if(! $user, 400, "Invalid user ID: {$userId}");
        Gate::authorize('save', $user);

        return $user;
    }

    public function renderInput(Request $request, User $user): JsonResponse
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
