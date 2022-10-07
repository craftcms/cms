<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Image;
use craft\elements\Asset;
use craft\models\ImageTransformIndex;
use yii\base\Event;

/**
 * Image transformer operation event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class ImageTransformerOperationEvent extends Event
{
    /**
     * @var Asset The Asset that is associated with the event
     */
    public Asset $asset;

    /**
     * @var ImageTransformIndex The image transform model associated with the event.
     */
    public ImageTransformIndex $imageTransformIndex;

    /**
     * @var string The resulting file path that will either be created or deleted.
     */
    public string $path;

    /**
     * The Image instance that was just saved.
     */
    public ?Image $image;

    /**
     * @var string The temporary file path.
     * @since 4.3.0
     */
    public ?string $tempPath = null;
}
