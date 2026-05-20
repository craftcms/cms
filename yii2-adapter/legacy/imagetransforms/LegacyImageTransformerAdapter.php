<?php

namespace craft\imagetransforms;

use Craft;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Image\Contracts\AssetTransformerInterface;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\ImageTransformHelper;

/**
 * @deprecated 6.0.0
 */
class LegacyImageTransformerAdapter implements AssetTransformerInterface
{
    public function __construct(
        private readonly string $type,
    ) {
    }

    public static function handle(): string
    {
        return 'legacy-image-transformer';
    }

    public static function displayName(): string
    {
        return 'Legacy image transformer';
    }

    public static function gqlArguments(): array
    {
        return [];
    }

    public function getTransformUrl(Asset $asset, ImageTransform $imageTransform, bool $immediately): string
    {
        return Craft::$app->getImageTransforms()
            ->getImageTransformer($this->type)
            ->getTransformUrl($asset, $imageTransform, $immediately);
    }

    public function invalidateAssetTransforms(Asset $asset): void
    {
        Craft::$app->getImageTransforms()
            ->getImageTransformer($this->type)
            ->invalidateAssetTransforms($asset);
    }

    public function getTransformString(ImageTransform $imageTransform, bool $ignoreHandle = false): string
    {
        return ImageTransformHelper::getTransformString($imageTransform, $ignoreHandle);
    }

    public function getImageTransformSettingsHtml(ImageTransform $imageTransform, bool $readOnly = false): ?string
    {
        return null;
    }

    public function getFilesystemSettingsHtml(FsInterface $filesystem, bool $readOnly = false): ?string
    {
        return null;
    }
}
