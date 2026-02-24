<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\queuemanager;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\assets\vue\VueAsset;

/**
 * Asset bundle for the Queue manager
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.4.0
 */
class QueueManagerAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';

    /**
     * @inheritdoc
     */
    public $depends = [
        CpAsset::class,
        VueAsset::class,
    ];

    /**
     * @inheritdoc
     */
    public $css = [
        'css/queue-manager.css',
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'queue-manager.js',
    ];
}
