<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\models\ImageTransform;
use yii\base\Event;

/**
 * Image transform event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class ImageTransformEvent extends Event
{
    /**
     * @var ImageTransform The image transform model associated with the event.
     */
    public ImageTransform $imageTransform;

    /**
     * @var bool Whether the image transform is brand new
     */
    public bool $isNew = false;
}
