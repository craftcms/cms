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
 * # Indexes all assets on all volumes. Optional argument is whether or not to cache images.
 * Default is true.
 * craft asset-indexing/all false
 * # Indexes all assets on a specified volume (EXAMPLE). Second (optional) argument is
 * is whether or not to cache images. Default is true.
 * craft asset-indexing/one EXAMPLE false
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
     * @param boolean $cacheImages whether or not to cache images. Default is true.
     * @return boolean.
     * @throws \Throwable $e.
     */
    public function actionAll($cacheImages = true)
    {
        try {
            if ($volumes = Craft::$app->getVolumes()->getAllVolumes()) {
                foreach ($volumes as $volume) {
                    foreach (Craft::$app->getAssetIndexer()->getIndexListOnVolume($volume) as $item) {
                        Craft::$app->getAssetIndexer()->indexFile(
                            $volume,
                            $item['path'],
                            '',
                            $cacheImages
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
     * @param boolean $cacheImages whether or not to cache images. Default is true.
     * @return boolean.
     * @throws \Throwable $e.
     */

    public function actionOne($name, $cacheImages = true)
    {
        try {
            if ($volume = Craft::$app->getVolumes()->getVolumeByHandle($name)) {
                foreach (Craft::$app->getAssetIndexer()->getIndexListOnVolume($volume) as $item) {
                    Craft::$app->getAssetIndexer()->indexFile(
                        $volume,
                        $item['path'],
                        '',
                        $cacheImages
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
