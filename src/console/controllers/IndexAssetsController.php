<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\console\Controller;
use craft\db\Table;
use craft\elements\Asset;
use craft\errors\AssetDisallowedExtensionException;
use craft\errors\AssetNotIndexableException;
use craft\errors\FsObjectNotFoundException;
use craft\errors\MissingAssetException;
use craft\errors\MissingVolumeFolderException;
use craft\helpers\Db;
use craft\models\FsListing;
use craft\models\Volume;
use Throwable;
use yii\console\ExitCode;
use yii\db\Exception;
use yii\helpers\Console;

/**
 * Allows you to re-index assets in volumes.
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
    public bool $cacheRemoteImages = false;

    /**
     * @var bool Whether to auto-create new asset records when missing.
     */
    public bool $createMissingAssets = true;

    /**
     * @var bool Whether to delete all the asset records that have their files missing.
     */
    public bool $deleteMissingAssets = false;

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        $options = parent::options($actionID);
        $options[] = 'cacheRemoteImages';
        $options[] = 'createMissingAssets';
        $options[] = 'deleteMissingAssets';
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
     * Re-indexes assets from the given volume handle.
     *
     * @param string $handle The handle of the volume to index.
     * You can optionally provide a volume sub-path, e.g. `php craft index-assets/one volume-handle/path/to/folder`.
     * @param int $startAt Index of the asset to start with, which defaults to `0`.
     * @return int
     * @since 3.1.4
     */
    public function actionOne(string $handle, int $startAt = 0): int
    {
        $path = '';

        if (str_contains($handle, '/')) {
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
     * Removes all CLI indexing sessions.
     *
     * @return int
     * @since 4.0.0
     */
    public function actionCleanup(): int
    {
        $total = Craft::$app->getAssetIndexer()->removeCliIndexingSessions();

        $this->stdout('Removed ' . $total . ' CLI indexing session' . ($total !== 1 ? 's' : '') . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * Indexes the assets in the given volumes.
     *
     * @param Volume[] $volumes
     * @param string $path the subfolder path
     * @param int $startAt
     * @return int
     * @throws MissingAssetException
     * @throws FsObjectNotFoundException
     * @throws Exception
     */
    private function _indexAssets(array $volumes, string $path = '', int $startAt = 0): int
    {
        $assetIndexer = Craft::$app->getAssetIndexer();

        $this->stdout(PHP_EOL);

        $session = $assetIndexer->createIndexingSession($volumes, $this->cacheRemoteImages, true);

        foreach ($volumes as $volume) {
            $this->stdout('Indexing assets in ', Console::FG_YELLOW);
            $this->stdout($volume->name, Console::FG_CYAN);
            $this->stdout(' ...' . PHP_EOL, Console::FG_YELLOW);
            $fileList = $assetIndexer->getIndexListOnVolume($volume, $path);

            $index = 0;
            /** @var MissingAssetException[] $missingRecords */
            $missingRecords = [];
            $missingRecordsByFilename = [];

            /** @var FsListing $item */
            foreach ($fileList as $item) {
                $count = $index;
                $this->stdout('    > #' . $count . ': ');
                $this->stdout($item->getUri() . ($item->getIsDir() ? '/' : ''), Console::FG_CYAN);
                $this->stdout(' ... ');
                if ($index++ < $startAt) {
                    $this->stdout('skipped' . PHP_EOL, Console::FG_YELLOW);
                    continue;
                }

                try {
                    if ($item->getIsDir()) {
                        $assetIndexer->indexFolderByListing((int)$volume->id, $item, $session->id, $this->createMissingAssets);
                    } else {
                        $assetIndexer->indexFileByListing((int)$volume->id, $item, $session->id, $this->cacheRemoteImages, $this->createMissingAssets);
                    }
                } catch (MissingAssetException $e) {
                    $this->stdout('missing' . PHP_EOL, Console::FG_YELLOW);
                    $missingRecords[] = $e;
                    $missingRecordsByFilename[$e->filename][] = $e;
                    continue;
                } catch (MissingVolumeFolderException $e) {
                    $this->stdout('missing' . PHP_EOL, Console::FG_YELLOW);
                    $missingRecords[] = $e;
                    continue;
                } catch (AssetDisallowedExtensionException|AssetNotIndexableException $e) {
                    $this->stdout('skipped: ' . $e->getMessage() . PHP_EOL, Console::FG_YELLOW);
                    continue;
                } catch (Throwable $e) {
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
                $this->stdout(($totalMissing === 1 ? 'One record is missing:' : "$totalMissing records are missing:") . PHP_EOL, Console::FG_YELLOW);
                foreach ($missingRecords as $e) {
                    $this->stdout("- {$e->volume->name}/{$e->indexEntry->uri}" . ($e instanceof MissingVolumeFolderException ? '/' : '') . PHP_EOL);
                }
                $this->stdout(PHP_EOL);
            }
        }

        // Manually close the indexing session.
        $session->actionRequired = true;
        $missingEntries = $assetIndexer->getMissingEntriesForSession($session);
        $missingFiles = $missingEntries['files'];
        $missingFolders = $missingEntries['folders'];

        $maybes = false;

        if (!empty($missingFiles)) {
            $totalMissing = count($missingFiles);
            $this->stdout(($totalMissing === 1 ? 'One recorded asset is missing its file:' : "$totalMissing recorded assets are missing their files:") . PHP_EOL, Console::FG_YELLOW);
            foreach ($missingFiles as $assetId => $filePath) {
                $this->stdout("- $filePath ($assetId)");
                $filename = basename($filePath);
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

        if (!empty($missingFolders)) {
            $totalMissing = count($missingFolders);
            $this->stdout(($totalMissing === 1 ? 'One missing folder:' : "$totalMissing missing folders:") . PHP_EOL, Console::FG_YELLOW);
            foreach ($missingFolders as $folderId => $folderPath) {
                $this->stdout("- $folderPath ($folderId)");
                $this->stdout(PHP_EOL);
            }
            $this->stdout(PHP_EOL);
        }

        $remainingMissingFiles = $missingFiles;

        if ($maybes && $this->interactive && $this->confirm('Fix asset locations?')) {
            foreach ($missingFiles as $assetId => $filePath) {
                unset($remainingMissingFiles[$assetId]);
                $filename = basename($filePath);
                if (isset($missingRecordsByFilename[$filename])) {
                    $e = $this->_chooseMissingRecord($filePath, $missingRecordsByFilename[$filename]);
                    if (!$e) {
                        $this->stdout("Skipping asset $assetId" . PHP_EOL);
                        continue;
                    }
                    $this->stdout("Relocating asset $assetId to {$e->volume->name}/{$e->indexEntry->uri} ... ");
                    Db::update(Table::ASSETS, [
                        'volumeId' => $e->volume->id,
                        'folderId' => $e->folder->id,
                    ], [
                        'id' => $assetId,
                    ]);
                    $this->stdout('reindexing ... ');
                    $assetIndexer->indexFileByEntry($e->indexEntry, $this->cacheRemoteImages, false);
                    $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
                }
            }

            $this->stdout('Done fixing asset locations.' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        }

        if (!empty($remainingMissingFiles) && $this->deleteMissingAssets) {
            $assetIds = array_keys($remainingMissingFiles);
            $totalMissingFiles = count($remainingMissingFiles);
            $this->stdout('Deleting the' . ($totalMissingFiles > 1 ? ' ' . $totalMissingFiles : '') . ' missing asset record' . ($totalMissingFiles > 1 ? 's' : '') . ' ... ');

            /** @var Asset[] $assets */
            $assets = Asset::find()->id($assetIds)->all();
            foreach ($assets as $asset) {
                Craft::$app->getImageTransforms()->deleteCreatedTransformsForAsset($asset);
                $asset->keepFileOnDelete = true;
                Craft::$app->getElements()->deleteElement($asset);
            }

            $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        }

        if (!empty($missingFolders) && $this->deleteMissingAssets) {
            $totalMissingFolders = count($missingFolders);
            $this->stdout('Deleting the' . ($totalMissingFolders > 1 ? ' ' . $totalMissingFolders : '') . ' missing folder record' . ($totalMissingFolders > 1 ? 's' : '') . ' ... ');

            Craft::$app->getAssets()->deleteFoldersByIds(array_keys($missingFolders), false);

            $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        }

        $assetIndexer->stopIndexingSession($session);

        return ExitCode::OK;
    }

    /**
     * @param string $path
     * @param MissingAssetException[] $missingRecords
     * @return MissingAssetException|null
     */
    private function _chooseMissingRecord(string $path, array $missingRecords): ?MissingAssetException
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

        $selection = (int)$this->prompt('>', [
            'validator' => function($input) use ($missingRecords) {
                return !$input || (is_numeric($input) && isset($missingRecords[$input - 1]));
            },
        ]);

        return $selection ? $missingRecords[$selection - 1] : null;
    }
}
