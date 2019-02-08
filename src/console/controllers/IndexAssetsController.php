<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;

use craft\base\Volume;
use craft\base\VolumeInterface;
use yii\console\ExitCode;
use yii\helpers\Console;
use yii\console\Controller;

/**
 * Re-indexes assets in volumes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.2
 */
class IndexAssetsController extends Controller
{
    /**
     * @inheritdoc
     */
    public $defaultAction = 'one';

    /**
     * @var bool Whether remote-stored images should be locally cached in the process.
     */
    public $cacheRemoteImages = false;

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        $options = parent::options($actionID);
        $options[] = 'cacheRemoteImages';
        return $options;
    }

    /**
     * Re-indexes assets across all volumes.
     *
     * @return int
     */
    public function actionAll(): int
    {
        $volumes = Craft::$app->getVolumes()->getAllVolumes();

        if (empty($volumes)) {
            $this->stdout('No volumes exist.' . PHP_EOL, Console::FG_YELLOW);
            return ExitCode::OK;
        }

        return $this->_indexAssets($volumes);
    }

    /**
     * Re-indexes assets from the given volume handle ($startAt = 0).
     *
     * @param string $handle The handle of the volume to index
     * @param int $startAt
     * @return int
     */
    public function actionOne($handle, $startAt = 0)
    {
        $path = '';

        if (strpos($handle, '/') !== false) {
            $parts = explode('/', $handle);
            $handle = array_shift($parts);
            $path = implode('/', $parts);
        }

        $volume = Craft::$app->getVolumes()->getVolumeByHandle($handle);

        if (!$volume) {
            $this->stdout('No volume exists with the handle “' . $handle . '”.', Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return $this->_indexAssets([$volume], $path, $startAt);
    }

    /**
     * Indexes the assets in the given volumes.
     *
     * @param VolumeInterface[] $volumes
     * @param string $path the subfolder path
     * @param int $startAt
     * @return int
     */
    private function _indexAssets(array $volumes, string $path = '', $startAt = 0): int
    {
        $assetIndexer = Craft::$app->getAssetIndexer();
        $session = $assetIndexer->getIndexingSessionId();

        $this->stdout(PHP_EOL);

        foreach ($volumes as $volume) {
            /** @var Volume $volume */
            $this->stdout('Indexing assets in ', Console::FG_YELLOW);
            $this->stdout($volume->name, Console::FG_CYAN);
            $this->stdout(' ...' . PHP_EOL, Console::FG_YELLOW);
            $fileList = array_filter($assetIndexer->getIndexListOnVolume($volume, $path),
                function ($entry) {
                    return $entry['type'] !== 'dir';
                }
            );

            $startAt = (is_numeric($startAt) && $startAt < count($fileList)) ? (int)$startAt : 0;

            $index = 0;
            foreach ($fileList as $item) {
                $count = $index;
                $this->stdout('    > #' . $count . ': ');
                $this->stdout($item['path'], Console::FG_CYAN);
                $this->stdout(' ... ');
                if ($index++ < $startAt) {
                    $this->stdout('skipped' . PHP_EOL, Console::FG_YELLOW);
                    continue;
                }
                try {
                    $assetIndexer->indexFile($volume, $item['path'], $session, $this->cacheRemoteImages);
                } catch (\Throwable $e) {
                    $this->stdout('error: ' . $e->getMessage() . PHP_EOL . PHP_EOL, Console::FG_RED);
                    Craft::$app->getErrorHandler()->logException($e);
                    return ExitCode::UNSPECIFIED_ERROR;
                }

                $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
            }

            $this->stdout('Done indexing assets in ', Console::FG_GREEN);
            $this->stdout($volume->name, Console::FG_CYAN);
            $this->stdout('.' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        }

        return ExitCode::OK;
    }
}
