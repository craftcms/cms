<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use craft\base\Image as BaseImage;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\ImageTransformHelper;

/**
 * Image Transforms helper.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see ImageTransformHelper} instead.
 */
class ImageTransforms
{
    /**
     * @var string The pattern to use for matching against a transform string.
     */
    public const TRANSFORM_STRING_PATTERN = ImageTransformHelper::TRANSFORM_STRING_PATTERN;

    /**
     * Normalize a transform from handle or a set of properties to an ImageTransform.
     */
    public static function normalizeTransform(mixed $transform): ?ImageTransform
    {
        return ImageTransformHelper::normalizeTransform($transform);
    }

    /**
     * Get the transform string for a given asset image transform.
     */
    public static function getTransformString(ImageTransform $transform, bool $ignoreHandle = false): string
    {
        return ImageTransformHelper::getTransformString($transform, $ignoreHandle);
    }

    /**
     * Parses a transform string.
     */
    public static function parseTransformString(string $str): array
    {
        return ImageTransformHelper::parseTransformString($str);
    }

    /**
     * Create an ImageTransform from a string.
     */
    public static function createTransformFromString(string $transformString): ImageTransform
    {
        return ImageTransformHelper::createTransformFromString($transformString);
    }

    /**
     * Extend a transform by taking an existing transform and overriding its parameters.
     */
    public static function extendTransform(ImageTransform $transform, array $parameters): ImageTransform
    {
        return ImageTransformHelper::extendTransform($transform, $parameters);
    }

    /**
     * Get a local image source to use for transforms.
     */
    public static function getLocalImageSource(Asset $asset): string
    {
        return ImageTransformHelper::getLocalImageSource($asset);
    }

    /**
     * Store a local image copy to a destination path.
     */
    public static function storeLocalSource(string $source, string $destination = ''): void
    {
        ImageTransformHelper::storeLocalSource($source, $destination);
    }

    /**
     * Generates an image transform for an asset.
     */
    public static function generateTransform(
        Asset $asset,
        ImageTransform $transform,
        ?callable $heartbeat = null,
        ?BaseImage &$image = null,
    ): string {
        return ImageTransformHelper::generateTransform($asset, $transform, $heartbeat, $image);
    }

    /**
     * Detect the auto web-safe format for the Asset.
     */
    public static function detectTransformFormat(Asset $asset): string
    {
        return ImageTransformHelper::detectTransformFormat($asset);
    }
}
