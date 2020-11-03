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
 * Asset Thumbnail event
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class AssetThumbEvent extends Event
{
    /**
     * @var Asset The Asset a thumbnail was requested for
     */
    public $asset;

    /**
     * @var int Requested thumbnail width
     */
    public $width;

    /**
     * @var int Requested thumbnail height
     */
    public $height;

    /**
     * @var bool Whether the thumbnail should be generated if it doesn't exist yet
     */
    public $generate;

    /**
     * @var string|false|null Thumbnail path that should be used in place of the
     * Craft-defined thumbnail path, or `false` if one doesn't exist yet and
     * [[generate]] is `false`. Leave `null` if Craft’s thumbnail generation
     * should be used.
     */
    public $path;
}
