<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;

use yii\helpers\Console;
use yii\console\Controller;


/**
 * Manages Craft asset indexing.
 * This command indexes assets on all volumes or specified volumes.
 * ~~~
 * # Indexes all assets on all volumes.
 * craft asset-indexing/all
 * # Indexes all assets on a specified volume (EXAMPLE).
 * craft asset-indexing/one EXAMPLE
 * ~~~
 *
 * @since 3.1
 */
class AssetIndexingController extends Controller
{
    // Properties
    // =========================================================================
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

    }

    /**
     * Indexes all assets on all volumes.
     *
     * This command gives a CLI way to index all assets.
     *
     * ```
     * craft asset-indexing/all
     * ```
     *
     * @param string $name the name of the volume to index. This should only contain
     * letters, digits, and underscores.
     * @return boolean.
     * @throws \Throwable $e.
     */
    public function actionAll()
    {
        try {
            if ($volumes = Craft::$app->getVolumes()->getAllVolumes()) {
                foreach ($volumes as $volume) {
                    foreach (Craft::$app->getAssetIndexer()->getIndexListOnVolume($volume) as $item) {
                        Craft::$app->getAssetIndexer()->indexFile(
                            $volume,
                            $item['path'],
                            '',
                            false
                        );
                    }
                }
            } else {
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            echo $e->getMessage();
            return false;
        }
    }

    /**
     * Indexes all assets on one specific volume.
     *
     * This command gives a CLI way to index all assets.
     *
     * ```
     * craft asset-indexing/one EXAMPLE
     * ```
     *
     * @param string $name the name of the volume to index. This should only contain
     * letters, digits, and underscores.
     * @return boolean.
     * @throws \Throwable $e.
     */

    public function actionOne($name)
    {
        try {
            if ($volume = Craft::$app->getVolumes()->getVolumeByHandle($name)) {
                foreach (Craft::$app->getAssetIndexer()->getIndexListOnVolume($volume) as $item) {
                    Craft::$app->getAssetIndexer()->indexFile(
                        $volume,
                        $item['path'],
                        '',
                        false
                    );
                }
            } else {
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            echo $e->getMessage();
            return false;
        }
    }
}
