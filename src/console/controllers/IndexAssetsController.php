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
use craft\console\Controller;
use craft\db\Table;
use craft\errors\MissingAssetException;
use craft\errors\VolumeObjectNotFoundException;
use yii\console\ExitCode;
use yii\db\Exception;
use yii\helpers\Console;

/**
 * Allows you to re-indexes assets in volumes.
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
     * @var bool Whether to auto-create new asset records when missing.
     */
    public $createMissingAssets = true;

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        $options = parent::options($actionID);
        $options[] = 'cacheRemoteImages';
        $options[] = 'createMissingAssets';
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
    public function actionOne($handle, $startAt = 0): int
    {
        $path = '';

        if (strpos($handle, '/') !== false) {
            $parts = explode('/', $handle);
            $handle = array_shift($parts);
            $path = implode('/', $parts);
        }

        $volume = Craft::$app->getVolumes()->getVolumeByHandle($handle);

        if (!$volume) {
            $this->stderr("No volume exists with the handle “{$handle}”." . PHP_EOL, Console::FG_RED);
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
     * @throws MissingAssetException
     * @throws VolumeObjectNotFoundException
     * @throws Exception
     */
    private function _indexAssets(array $volumes, string $path = '', $startAt = 0): int
    {
        $assetIndexer = Craft::$app->getAssetIndexer();
        $sessionId = $assetIndexer->getIndexingSessionId();

        $this->stdout(PHP_EOL);

        foreach ($volumes as $volume) {
            /** @var Volume $volume */
            $this->stdout('Indexing assets in ', Console::FG_YELLOW);
            $this->stdout($volume->name, Console::FG_CYAN);
            $this->stdout(' ...' . PHP_EOL, Console::FG_YELLOW);
            $fileList = array_filter($assetIndexer->getIndexListOnVolume($volume, $path), function($entry) {
                return $entry['type'] !== 'dir';
            });

            $startAt = (is_numeric($startAt) && $startAt < count($fileList)) ? (int)$startAt : 0;

            $index = 0;
            /** @var MissingAssetException[] $missingRecords */
            $missingRecords = [];
            $missingRecordsByFilename = [];

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
                    $assetIndexer->indexFile($volume, $item['path'], $sessionId, $this->cacheRemoteImages, $this->createMissingAssets);
                } catch (MissingAssetException $e) {
                    $this->stdout('missing' . PHP_EOL, Console::FG_YELLOW);
                    $missingRecords[] = $e;
                    $missingRecordsByFilename[$e->filename][] = $e;
                    continue;
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

            if (!$this->createMissingAssets && !empty($missingRecords)) {
                $totalMissing = count($missingRecords);
                $this->stdout(($totalMissing === 1 ? 'One file is missing its record:' : "{$totalMissing} files are missing their records:") . PHP_EOL, Console::FG_YELLOW);
                foreach ($missingRecords as $e) {
                    $this->stdout("- {$e->volume->name}/{$e->indexEntry->uri}" . PHP_EOL);
                }
                $this->stdout(PHP_EOL);
            }

            $missingFiles = $assetIndexer->getMissingFiles($sessionId);
            $maybes = false;
            if (!empty($missingFiles)) {
                $totalMissing = count($missingFiles);
                $this->stdout(($totalMissing === 1 ? 'One recorded asset is missing its file:' : "{$totalMissing} recorded assets are missing their files:") . PHP_EOL, Console::FG_YELLOW);
                foreach ($missingFiles as $assetId => $path) {
                    $this->stdout("- {$path} ({$assetId})");
                    $filename = basename($path);
                    if (isset($missingRecordsByFilename[$filename])) {
                        $maybes = true;
                        $maybePaths = [];
                        foreach ($missingRecordsByFilename[$filename] as $e) {
                            /** @var MissingAssetException $e */
                            $maybePaths[] = "{$e->volume->name}/{$e->indexEntry->uri}";
                        }
                        $this->stdout(' (maybe ' . implode(', ', $maybePaths) . ')');
                    }
                    $this->stdout(PHP_EOL);
                }
                $this->stdout(PHP_EOL);
            }
        }

        if ($maybes && $this->confirm('Fix asset locations?')) {
            $db = Craft::$app->getDb();
            foreach ($missingFiles as $assetId => $path) {
                $filename = basename($path);
                if (isset($missingRecordsByFilename[$filename])) {
                    $e = $this->_chooseMissingRecord($path, $missingRecordsByFilename[$filename]);
                    if (!$e) {
                        $this->stdout("Skipping asset {$assetId}" . PHP_EOL);
                        continue;
                    }
                    $this->stdout("Relocating asset {$assetId} to {$e->volume->name}/{$e->indexEntry->uri} ... ");
                    $db->createCommand()
                        ->update(Table::ASSETS, [
                            'volumeId' => $e->volume->id,
                            'folderId' => $e->folder->id,
                        ], ['id' => $assetId])
                        ->execute();
                    $this->stdout('reindexing ... ');
                    $assetIndexer->indexFileByEntry($e->indexEntry, $this->cacheRemoteImages, false);
                    $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
                }
            }

            $this->stdout('Done fixing asset locations.' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        }

        return ExitCode::OK;
    }

    /**
     * @param string $path
     * @param MissingAssetException[] $missingRecords
     * @return MissingAssetException|null
     */
    private function _chooseMissingRecord(string $path, array $missingRecords)
    {
        if (count($missingRecords) === 1) {
            // Only one asset with the same name. Probably safe to just go with that.
            return $missingRecords[0];
        }

        $this->stdout('What is the new location for ');
        $this->stdout($path, Console::FG_CYAN);
        $this->stdout('? (leave blank to skip)' . PHP_EOL);

        foreach ($missingRecords as $i => $e) {
            $this->stdout($i + 1 . ') ', Console::FG_CYAN);
            $this->stdout("{$e->volume->name}/{$e->indexEntry->uri}" . PHP_EOL);
        }

        $selection = $this->prompt('>', [
            'validator' => function($input) use ($missingRecords) {
                return !$input || (is_numeric($input) && isset($missingRecords[$input - 1]));
            }
        ]);

        return $selection ? $missingRecords[$selection - 1] : null;
    }
}
