<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\elements\Asset;
use yii\base\Event;

/**
 * Define asset thumb URL event class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class DefineAssetThumbUrlEvent extends Event
{
    /**
     * @var Asset The asset which the thumbnail should be for.
     */
    public Asset $asset;

    /**
     * @var int Requested thumbnail width
     */
    public int $width;

    /**
     * @var int Requested thumbnail height
     */
    public int $height;

    /**
     * @var bool Whether the thumbnail should be generated if it doesn't exist yet.
     */
    public bool $generate;

    /**
     * @var string|null Url to requested Asset that should be used instead.
     */
    public ?string $url = null;
}
