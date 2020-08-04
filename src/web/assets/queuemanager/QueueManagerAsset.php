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
use craft\web\View;

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
    public $js = [
        'queue-manager.min.js',
    ];

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view)
    {
        parent::registerAssetFiles($view);

        if ($view instanceof View) {
            $view->registerTranslations('app', [
                'Pending',
                'Reserved',
                'Finished',
                'Failed',
                'Are you sure you want to release the job “{description}”?',
                'Are you sure you want to restart the job “{description}”? Any progress could be lost.',
                'Are you sure you want to release all jobs in the queue?',
                'All jobs released.',
                'Job retried.',
                'Job restarted.',
                'Job released.',
                'Retrying all failed jobs.',
                'ID',
                '{num, number} {num, plural, =1{second} other{seconds}}',
                'Time to reserve',
                'Status',
                'Progress',
                'Description',
                'Error',
            ]);
        }
    }
}
