<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

/**
 * Save asset image event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.10.6
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Image\Events\ImageEditorSaving} instead.
 */
class SaveAssetImageEvent extends AssetEvent
{
    /**
     * @var bool Whether the original asset should be replaced, rather than saving the image as a new asset.
     */
    public bool $replace;

    /**
     * @var int The current viewport rotation in degrees.
     */
    public int $viewportRotation;

    /**
     * @var float The current image rotation in degrees.
     */
    public float $imageRotation;

    /**
     * @var array The crop data posted by the image editor.
     */
    public array $cropData;

    /**
     * @var array|null The focal point data posted by the image editor, if any.
     */
    public ?array $focalPoint = null;

    /**
     * @var array The image dimensions posted by the image editor.
     */
    public array $imageDimensions;

    /**
     * @var array|null The flip data posted by the image editor, if any.
     */
    public ?array $flipData = null;

    /**
     * @var float The current image zoom ratio.
     */
    public float $zoom = 1.0;

    /**
     * @var int|null The ID of the new asset saved by the event handler, if [[replace]] is `false`.
     */
    public ?int $newAssetId = null;
}
