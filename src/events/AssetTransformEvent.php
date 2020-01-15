<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\models\AssetTransform;
use yii\base\Event;

/**
 * Asset transform event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class AssetTransformEvent extends Event
{
    /**
     * @var AssetTransform|null The asset transform model associated with the event.
     */
    public $assetTransform;

    /**
     * @var bool Whether the asset transform is brand new
     */
    public $isNew = false;
}
