<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Contracts;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Image\Data\ImageTransform;

interface AssetTransformerInterface
{
    public static function handle(): string;

    public static function displayName(): string;

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function gqlArguments(): array;

    public function getTransformUrl(Asset $asset, ImageTransform $imageTransform, bool $immediately): string;

    public function invalidateAssetTransforms(Asset $asset): void;

    public function getTransformString(ImageTransform $imageTransform, bool $ignoreHandle = false): string;

    public function getSettingsHtml(ImageTransform $imageTransform, bool $readOnly = false): ?string;
}
