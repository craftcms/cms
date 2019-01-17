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
 * craft asset-indexing/all
 * # Indexes all assets on a specified volume (EXAMPLE_HANDLE).
 * Second (optional) argument is whether or not to cache images.
 * craft asset-indexing/one EXAMPLE_HANDLE
 * ~~~
 *
 * @since 3.1
 */
class AssetIndexingController extends Controller
{
    /**
     * Indexes all assets on all volumes.
     *
     * ```
     * craft asset-indexing/all
     * // Index all assets without caching images:
     * craft asset-indexing/all 0
     * ```
     *
     * @param boolean $cacheImages Whether or not to cache images.
     * @return boolean.
     * @throws \Throwable $e.
     */
    public function actionAll($cacheImages = 1)
    {
        try {
            $session = Craft::$app->getAssetIndexer()->getIndexingSessionId();
            if ($volumes = Craft::$app->getVolumes()->getAllVolumes()) {
                foreach ($volumes as $volume) {
                    foreach (Craft::$app->getAssetIndexer()->getIndexListOnVolume($volume) as $item) {
                        Craft::$app->getAssetIndexer()->indexFile(
                            $volume,
                            $item['path'],
                            $session,
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
     * ```
     * craft asset-indexing/one EXAMPLE_HANDLE
     * // Index volume without caching images:
     * craft asset-indexing/one EXAMPLE_HANDLE 0
     * ```
     *
     * @param string $name The name of the volume to index. This should only contain
     * letters, digits, and underscores.
     * @param boolean $cacheImages Whether or not to cache images.
     * @return boolean.
     * @throws \Throwable $e.
     */

    public function actionOne($name, $cacheImages = 1)
    {
        try {
            $session = Craft::$app->getAssetIndexer()->getIndexingSessionId();
            if ($volume = Craft::$app->getVolumes()->getVolumeByHandle($name)) {
                foreach (Craft::$app->getAssetIndexer()->getIndexListOnVolume($volume) as $item) {
                    Craft::$app->getAssetIndexer()->indexFile(
                        $volume,
                        $item['path'],
                        $session,
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
