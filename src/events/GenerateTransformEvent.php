<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\elements\Asset;
use craft\models\ImageTransform;
use yii\base\Event;

/**
 * Asset generate transform event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class GenerateTransformEvent extends Event
{
    /**
     * @var Asset|null The asset which the transform should be for.
     */
    public ?Asset $asset;

    /**
     * @var ImageTransform|null Image transform representing the transform.
     */
    public ?ImageTransform $transform;

    /**
     * @var string|null Url to requested Asset that should be used instead.
     */
    public ?string $url = null;
}
