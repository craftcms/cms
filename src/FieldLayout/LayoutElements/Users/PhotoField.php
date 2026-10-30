<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements\Users;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseNativeField;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Controls\AssetSelect;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use InvalidArgumentException;
use Override;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
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

    #[Override]
    protected function uiControl(FieldLayoutElementContext $context): ?Control
    {
        if ($context->element && ! $context->element instanceof User) {
            throw new InvalidArgumentException(sprintf('%s can only be used in user field layouts.', self::class));
        }

        if (! $context->element?->id || ! ProjectConfig::get('users.photoVolumeUid')) {
            return null;
        }

        $folder = app(Users::class)->userPhotoFolder($context->element);
        $source = 'volume:'.$folder->getVolume()->uid;

        if ($folder->parentId) {
            $source .= '/folder:'.$folder->uid;
        }

        return AssetSelect::make($this->attribute())
            ->elementType(Asset::class)
            ->sources([$source])
            ->criteria(['volumeId' => $folder->volumeId, 'folderId' => $folder->id, 'kind' => 'image'])
            ->canUpload()
            ->uploadFolderId($folder->id)
            ->showFolders(false)
            ->limit(1)
            ->value($context->element->photoId ? [$context->element->photoId] : []);
    }

    /** @return list<array<string, mixed>> */
    #[Override]
    protected function actionMenuItems(?ElementInterface $element = null, bool $static = false): array
    {
        $items = [];

        if (currentUser()?->isAdmin()) {
            $items[] = $this->copyAttributeAction();
        }

        return $items;
    }

    #[Override]
    protected function uiIcon(): ?string
    {
        return 'circle-user';
    }
}
