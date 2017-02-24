<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\events;

use craft\base\Image;
use craft\elements\Asset;
use craft\models\AssetTransformIndex;
use yii\base\Event;

/**
 * Asset generate transform event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class GenerateTransformEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var AssetTransformIndex Asset transform index that is being generated.
     */
    public $transformIndex;

    /**
     * @var Asset The Asset that is being transformed.
     */
    public $asset;

    /**
     * @var Image
     */
    public $image;

    /**
     * @var string|null Path to the modified image that should be used instead.
     */
    public $tempPath;
}
