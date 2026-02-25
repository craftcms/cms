<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\imagetransforms;

use Craft;
use craft\base\imagetransforms\EagerImageTransformerInterface;
use craft\base\imagetransforms\ImageEditorTransformerInterface;
use craft\base\imagetransforms\ImageTransformerInterface;
use craft\events\ImageTransformerOperationEvent;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\Data\ImageTransformIndex;
use CraftCms\Cms\Image\Events\DeletingTransformedImage;
use CraftCms\Cms\Image\Events\TransformingImage;
use CraftCms\Cms\Image\ImageTransformer as NewImageTransformer;
use Illuminate\Support\Facades\Event as EventFacade;
use yii\base\Component;

/**
 * ImageTransformer transforms image assets using GD or ImageMagick.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see NewImageTransformer} instead.
 *
 * @property-read int $editedImageHeight
 * @property-read int $editedImageWidth
 * @property-read array $pendingTransformIndexIds
 */
class ImageTransformer extends Component implements EagerImageTransformerInterface, ImageEditorTransformerInterface, ImageTransformerInterface
{
    /**
     * @event ImageTransformerOperationEvent The event that is fired when an image is transformed
     */
    public const EVENT_TRANSFORM_IMAGE = 'transformImage';

    /**
     * @event ImageTransformerOperationEvent The event that is fired when a generated image transform is deleted
     */
    public const EVENT_DELETE_TRANSFORMED_IMAGE = 'deleteTransformedImage';

    private ?NewImageTransformer $_transformer = null;

    private function transformer(): NewImageTransformer
    {
        return $this->_transformer ??= new NewImageTransformer();
    }

    /**
     * {@inheritdoc}
     */
    public function getTransformUrl(Asset $asset, ImageTransform $imageTransform, bool $immediately): string
    {
        return $this->transformer()->getTransformUrl($asset, $imageTransform, $immediately);
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateAssetTransforms(Asset $asset): void
    {
        $this->transformer()->invalidateAssetTransforms($asset);
    }

    public function deleteImageTransformFile(Asset $asset, ImageTransformIndex $transformIndex): void
    {
        $this->transformer()->deleteImageTransformFile($asset, $transformIndex);
    }

    /**
     * {@inheritdoc}
     */
    public function eagerLoadTransforms(array $transforms, array $assets): void
    {
        $this->transformer()->eagerLoadTransforms($transforms, $assets);
    }

    /**
     * Get a transform index row. If it doesn't exist - create one.
     *
     * @param ImageTransform|string|array|null $transform
     */
    public function getTransformIndex(Asset $asset, mixed $transform): ImageTransformIndex
    {
        return $this->transformer()->getTransformIndex($asset, $transform);
    }

    /**
     * Store a transform index data by its model.
     */
    public function storeTransformIndexData(ImageTransformIndex $index): ImageTransformIndex
    {
        $this->transformer()->storeTransformIndexData($index);

        return $index;
    }

    /**
     * Returns a list of pending transform index IDs.
     */
    public function getPendingTransformIndexIds(): array
    {
        return $this->transformer()->getPendingTransformIndexIds();
    }

    /**
     * Get a transform index model by a row id.
     */
    public function getTransformIndexModelById(int $transformId): ?ImageTransformIndex
    {
        return $this->transformer()->getTransformIndexModelById($transformId);
    }

    /**
     * {@inheritdoc}
     */
    public function startImageEditing(Asset $asset): void
    {
        $this->transformer()->startImageEditing($asset);
    }

    /**
     * {@inheritdoc}
     */
    public function flipImage(bool $flipX, bool $flipY): void
    {
        $this->transformer()->flipImage($flipX, $flipY);
    }

    /**
     * {@inheritdoc}
     */
    public function scaleImage(int $width, int $height): void
    {
        $this->transformer()->scaleImage($width, $height);
    }

    /**
     * {@inheritdoc}
     */
    public function rotateImage(float $degrees): void
    {
        $this->transformer()->rotateImage($degrees);
    }

    /**
     * {@inheritdoc}
     */
    public function getEditedImageWidth(): int
    {
        return $this->transformer()->getEditedImageWidth();
    }

    /**
     * {@inheritdoc}
     */
    public function getEditedImageHeight(): int
    {
        return $this->transformer()->getEditedImageHeight();
    }

    /**
     * {@inheritdoc}
     */
    public function crop(int $x, int $y, int $width, int $height): void
    {
        $this->transformer()->crop($x, $y, $width, $height);
    }

    /**
     * {@inheritdoc}
     */
    public function finishImageEditing(): string
    {
        return $this->transformer()->finishImageEditing();
    }

    /**
     * {@inheritdoc}
     */
    public function cancelImageEditing(): string
    {
        return $this->transformer()->cancelImageEditing();
    }

    public static function registerEvents(): void
    {
        EventFacade::listen(TransformingImage::class, function(TransformingImage $event) {
            $legacyTransformer = Craft::$app->getImageTransforms()->getImageTransformer(self::class);

            if (!$legacyTransformer->hasEventHandlers(self::EVENT_TRANSFORM_IMAGE)) {
                return;
            }

            $legacyEvent = new ImageTransformerOperationEvent([
                'asset' => $event->asset,
                'imageTransformIndex' => $event->imageTransformIndex,
                'path' => '',
                'tempPath' => $event->tempPath,
            ]);
            $legacyTransformer->trigger(self::EVENT_TRANSFORM_IMAGE, $legacyEvent);
            $event->tempPath = $legacyEvent->tempPath;
        });

        EventFacade::listen(DeletingTransformedImage::class, function(DeletingTransformedImage $event) {
            $legacyTransformer = Craft::$app->getImageTransforms()->getImageTransformer(self::class);

            if (!$legacyTransformer->hasEventHandlers(self::EVENT_DELETE_TRANSFORMED_IMAGE)) {
                return;
            }

            $legacyTransformer->trigger(self::EVENT_DELETE_TRANSFORMED_IMAGE, new ImageTransformerOperationEvent([
                'asset' => $event->asset,
                'path' => $event->path,
            ]));
        });
    }
}
