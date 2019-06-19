<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\queuemanager;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\assets\momentjs\MomentJsAsset;
use craft\web\assets\vue\VueAsset;

/**
 * Asset bundle for the Queue manager
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class QueueManagerAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = __DIR__ . '/dist';

        $this->depends = [
            CpAsset::class,
            VueAsset::class
        ];

        // TODO: Once Babel is setup we can use $this->dotJs()
        $this->js = [
            'queue-manager.js',
        ];

        parent::init();
    }
}
