<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use craft\models\ImageTransform;

/**
 * Image transform event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Image\Events\SavingTransform}, {@see \CraftCms\Cms\Image\Events\TransformSaved}, {@see \CraftCms\Cms\Image\Events\DeletingTransform}, {@see \CraftCms\Cms\Image\Events\ApplyingTransformDelete}, or {@see \CraftCms\Cms\Image\Events\TransformDeleted} instead.
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
