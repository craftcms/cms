<?php

namespace craft\events;

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
     * @var array Asset volumes that should be available to be indexed.
     */
    public array $assetVolumes = [];
}
