<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements\Users;

use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseNativeField;
use CraftCms\Cms\Filesystem\Exceptions\InvalidSubpathException;
use CraftCms\Cms\Filesystem\Filesystems\Temp;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Folders;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\View\LegacyAssets\CpAsset;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Override;
use Throwable;

use function CraftCms\Cms\renderObjectTemplate;
use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

class PhotoField extends BaseNativeField
{
    #[Override]
    public bool $mandatory = true;

    #[Override]
    public string $attribute = 'photo';

    public function __construct($config = [])
    {
        // We didn't start removing autofocus from fields() until 3.5.6
        parent::__construct(Arr::except($config, [
            'mandatory',
            'attribute',
            'translatable',
            'required',
        ]));
    }

    #[Override]
    public function fields(): array
    {
        return Arr::except(parent::fields(), [
            'mandatory',
            'attribute',
            'translatable',
            'required',
        ]);
    }

    public function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return t('Photo');
    }

    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if ($element && ! $element instanceof User) {
            throw new InvalidArgumentException(sprintf('%s can only be used in user field layouts.', self::class));
        }

        if (! $element?->id) {
            return null;
        }

        if (! $volumeUid = ProjectConfig::get('users.photoVolumeUid')) {
            return null;
        }

        $volume = Volumes::getVolumeByUid($volumeUid);
        if (! $volume) {
            return null;
        }

        $folder = $this->userPhotoFolder($element);

        app(InternalAssetRegistry::class)->register(CpAsset::class);
        $inputId = sprintf('user-photo-%s', mt_rand());
        $namespacedInputId = InputNamespace::namespaceId($inputId);
        $uploadFs = $volume->getFs();
        $canUpload = ! $uploadFs instanceof Temp && Gate::check("saveAssets:$volume->uid");

        if ($canUpload) {
            HtmlStack::jsWithVars(fn ($inputId, $folderId) => <<<JS
const setUserPhotoUploadFolder = () => {
  const userPhotoInput = $('#' + $inputId).data('elementSelect');
  if (userPhotoInput?.uploader) {
    userPhotoInput.uploader.setParams({folderId: $folderId});
  } else if (!userPhotoInput || userPhotoInput.canUpload) {
    setTimeout(setUserPhotoUploadFolder, 0);
  } else {
    return;
  }
}
setUserPhotoUploadFolder();
JS, [
                $namespacedInputId,
                $folder->id,
            ]);
        }

        return template('_components/fieldtypes/Assets/input', [
            'id' => $inputId,
            'name' => 'photoId',
            'jsClass' => 'Craft.AssetSelectInput',
            'elementType' => Asset::class,
            'elements' => $element->getPhoto() ? [$element->getPhoto()] : [],
            'condition' => null,
            'criteria' => [
                'kind' => 'image',
                'folderId' => $folder->id,
                'siteId' => $element->siteId ?? Sites::getCurrentSite()->id,
            ],
            'sources' => ["volume:$volume->uid"],
            'storageKey' => 'userPhoto',
            'fieldId' => null,
            'single' => true,
            'limit' => 1,
            'defaultPlacement' => 'end',
            'selectionLabel' => t('Choose a photo'),
            'viewMode' => 'large',
            'showActionMenu' => true,
            'canUpload' => $canUpload,
            'fsType' => $uploadFs::class,
            'defaultFieldLayoutId' => $volume->fieldLayoutId ?? null,
            'defaultSource' => "volume:$volume->uid",
            'defaultSourcePath' => $folder->getSourcePathInfo() ? [$folder->getSourcePathInfo()] : null,
            'showFolders' => true,
            'showSourcePath' => true,
            'sourceElementId' => $element->id,
            'modalSettings' => [
                'defaultSiteId' => $element->siteId ?? null,
            ],
        ]);
    }

    private function userPhotoFolder(User $user): VolumeFolder
    {
        $volume = Volumes::getUserPhotoVolume();
        if (! $volume) {
            throw new InvalidArgumentException('Invalid user photo volume.');
        }

        $subpath = (string) ProjectConfig::get('users.photoSubpath');

        if ($subpath !== '') {
            try {
                $subpath = renderObjectTemplate($subpath, $user);
            } catch (Throwable) {
                throw new InvalidSubpathException($subpath);
            }
        }

        return Folders::ensureFolderByFullPathAndVolume($subpath, $volume);
    }

    #[Override]
    protected function actionMenuItems(?ElementInterface $element = null, bool $static = false): array
    {
        $items = [];

        if (Auth::user()?->isAdmin()) {
            $items[] = $this->copyAttributeAction();
        }

        return $items;
    }
}
