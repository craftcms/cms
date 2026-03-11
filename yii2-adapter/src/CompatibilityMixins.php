<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter;

use craft\base\Event as YiiEvent;
use craft\base\FieldLayoutComponent;
use CraftCms\Cms\Asset\Data\FolderCriteria as AssetFolderCriteria;
use CraftCms\Cms\Asset\Data\Volume as AssetVolume;
use CraftCms\Cms\Asset\Data\VolumeFolder as AssetVolumeFolder;
use CraftCms\Cms\Dashboard\Widgets\Widget;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Filesystem\Data\FsListing as FilesystemFsListing;
use CraftCms\Cms\Filesystem\Filesystems\Filesystem as FilesystemComponent;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\Data\ImageTransformIndex;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Yii2Adapter\Mixins\ElementMixin;
use CraftCms\Yii2Adapter\Mixins\ElementQueryMixin;
use CraftCms\Yii2Adapter\Mixins\UserMixin;
use CraftCms\Yii2Adapter\Mixins\ValidateMixin;
use CraftCms\Yii2Adapter\Mixins\VolumeMixin;

final readonly class CompatibilityMixins
{
    public function register(): void
    {
        Field::macro('trigger', function($name, mixed $event = null): void {
            Deprecator::log('Field-trigger', 'Calling ->trigger on a Field is deprecated. Switch to component events instead.');

            $event ??= new YiiEvent();

            YiiEvent::trigger($this, $name, $event);

            $this->dispatchComponentEvent($name, $event);
        });

        Element::mixin(new ValidateMixin());
        Element::mixin(new ElementMixin());
        Field::mixin(new ValidateMixin());
        FieldLayoutComponent::mixin(new ValidateMixin());
        FilesystemComponent::mixin(new ValidateMixin());
        ElementQuery::mixin(new ElementQueryMixin());
        User::mixin(new UserMixin());
        AssetFolderCriteria::mixin(new ValidateMixin());
        AssetVolume::mixin(new ValidateMixin());
        AssetVolume::mixin(new VolumeMixin());
        AssetVolumeFolder::mixin(new ValidateMixin());
        FilesystemFsListing::mixin(new ValidateMixin());
        Widget::mixin(new ValidateMixin());
        ImageTransform::mixin(new ValidateMixin());
        ImageTransformIndex::mixin(new ValidateMixin());
    }
}
