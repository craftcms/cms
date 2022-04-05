<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\elements\Asset;
use craft\models\AssetTransform;
use yii\base\Event;

/**
 * Get Asset url event class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class GetAssetUrlEvent extends Event
{
    /**
     * @var AssetTransform|string|array|null Asset transform index that is being generated (if any)
     */
    public $transform;

    /**
     * @var Asset The Asset that is being transformed.
     */
    public $asset;

    /**
     * @var string|null Url to requested Asset that should be used instead.
     */
    public $url;
}
