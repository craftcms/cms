<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\models\Volume;
use yii\base\Event;

/**
 * Authenticate User event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class AssetVolumeEventIndexing extends Event
{
    /**
     * @var Volume[] Asset volumes available to be indexed.
     */
    public array $assetVolumes = [];
}
