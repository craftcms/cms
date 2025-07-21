<?php

namespace Craft\Cms\Utility\Events;

use craft\models\Volume;

/**
 * ListVolumes class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 6.0.0
 */
class ListVolumes
{
    public function __construct(
        /** @var Volume[] The volumes to be listed. */
        public array $volumes
    ) {}
}
