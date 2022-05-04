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
 * Class DefineAssetThumbUrlEvent
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class DefineAssetThumbUrlEvent extends Event
{
    /**
     * @var Asset The asset a thumbnail has been requested for
     */
    public Asset $asset;

    /**
     * @var int The thumbnail width
     */
    public int $width;

    /**
     * @var int The thumbnail height
     */
    public int $height;

    /**
     * @var string|null The resulting thumbnail URL
     */
    public ?string $url = null;
}
