<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Commands\Concerns;

use craft\console\Application;
use CraftCms\Cms\Asset\Data\IndexingSession;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Exceptions\AssetDisallowedExtensionException;
use CraftCms\Cms\Asset\Exceptions\AssetNotIndexableException;
use CraftCms\Cms\Asset\Exceptions\MissingAssetException;
use CraftCms\Cms\Asset\Exceptions\MissingVolumeFolderException;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Filesystem\Data\FsListing;
use CraftCms\Cms\Image\ImageTransforms;
use CraftCms\Cms\Support\Facades\AssetIndexer;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Folders;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\select;

trait IndexesAssets
{
    private bool $cacheRemoteImages;

    private bool $deleteEmptyFolders;

    private bool $deleteMissingAssets;

    private bool $createMissingAssets;

    private function parseBooleanOption(string|int|bool $value): bool
    {
        return match (strtolower($value)) {
            '1', 'yes', 'y', 'true', 'on' => true,
            default => false,
        };
    }

    /**
     * @param  Volume[]  $volumes
     */
    protected function indexAssets(Application $craft, array $volumes, string $path = '', int $startAt = 0): void
    {
        $this->cacheRemoteImages = $this->hasOption('cacheRemoteImages') && $this->parseBooleanOption($this->option('cacheRemoteImages'));
        $this->deleteEmptyFolders = $this->parseBooleanOption($this->option('deleteEmptyFolders'));
        $this->deleteMissingAssets = $this->parseBooleanOption($this->option('deleteMissingAssets'));
        $this->createMissingAssets = $this->parseBooleanOption($this->option('createMissingAssets'));

        $this->newLine();

        $session = AssetIndexer::createIndexingSession(
            volumeList: $volumes,
            cacheRemoteImages: $this->cacheRemoteImages,
            isCli: true,
            listEmptyFolders: $this->deleteEmptyFolders,
        );

        $missingRecordsByFilename = collect($volumes)
            ->flatMap(fn (Volume $volume) => $this->processVolume($volume, $path, $startAt, $session))
            ->all();

        // Manually close the indexing session.
        $session->actionRequired = true;
        $missingEntries = AssetIndexer::getMissingEntriesForSession($session, $path);
        $missingFiles = $missingEntries['files'];
        $missingFolders = $missingEntries['folders'];

        $maybes = false;

        if (! empty($missingFiles)) {
            $maybes = $this->processMissingFiles($missingFiles, $missingRecordsByFilename);
        }

        if (! empty($missingFolders)) {
            $totalMissing = count($missingFolders);

            $this->warn($totalMissing === 1
                ? 'One missing or empty folder:'
                : "$totalMissing missing or empty folders:"
            );

            $this->components->bulletList(
                collect($missingFolders)
                    ->map(fn (string $folderPath, int $folderId) => "$folderPath ($folderId)")
                    ->all()
            );
        }

        $remainingMissingFiles = $missingFiles;

        if ($maybes && $this->input->isInteractive() && $this->confirm('Fix asset locations?', true)) {
            $remainingMissingFiles = $this->fixAssetLocations(
                $missingFiles,
                $remainingMissingFiles,
                $missingRecordsByFilename,
            );
        }

        if (! empty($remainingMissingFiles) && $this->deleteMissingAssets) {
            $assetIds = array_keys($remainingMissingFiles);
            $totalMissingFiles = count($remainingMissingFiles);

            $this->components->task(
                'Deleting the'.($totalMissingFiles > 1 ? ' '.$totalMissingFiles : '').' missing asset record'.Str::plural('record', $totalMissingFiles),
                function () use ($assetIds) {
                    /** @var ElementCollection<Asset> $assets */
                    $assets = Asset::find()->id($assetIds)->get();

                    foreach ($assets as $asset) {
                        app(ImageTransforms::class)->deleteCreatedTransformsForAsset($asset);
                        $asset->keepFileOnDelete = true;
                        Elements::deleteElement($asset);
                    }
                }
            );
        }

        if (! empty($missingFolders) && $this->deleteEmptyFolders) {
            $totalMissingFolders = count($missingFolders);

            $this->components->task(
                'Deleting the'.($totalMissingFolders > 1 ? ' '.$totalMissingFolders : '').' missing and empty '.Str::plural('folder', $totalMissingFolders),
                function () use ($missingFolders) {
                    Folders::deleteFoldersByIds(array_keys($missingFolders));
                }
            );
        }

        AssetIndexer::stopIndexingSession($session);
    }

    private function processVolume(
        Volume $volume,
        string $path,
        int $startAt,
        IndexingSession $session,
    ): array {
        $this->components->twoColumnDetail("Indexing assets in <fg=cyan>{$volume->name}</>");

        $fileList = AssetIndexer::getIndexListOnVolume($volume, $path);
        $fsSubpath = $volume->getSubpath();

        /** @var Collection<MissingAssetException|MissingVolumeFolderException> $missingRecords */
        $missingRecords = Collection::make();
        $missingRecordsByFilename = [];

        /** @var FsListing $item */
        foreach ($fileList as $index => $item) {
            $count = $index + 1;
            $description = "#{$count}: <fg=cyan>{$item->getAdjustedUri($fsSubpath)}".($item->getIsDir() ? '/' : '').'</>';

            if ($count < $startAt) {
                $this->components->twoColumnDetail($description, '<fg=yellow;options=bold>SKIPPED</>');

                continue;
            }

            try {
                $item->getIsDir()
                    ? AssetIndexer::indexFolderByListing($volume, $item, $session->id, $this->createMissingAssets)
                    : AssetIndexer::indexFileByListing($volume, $item, $session->id, $this->cacheRemoteImages, $this->createMissingAssets);
            } catch (MissingAssetException|MissingVolumeFolderException $e) {
                $missingRecords->add($e);

                if ($e instanceof MissingAssetException) {
                    $missingRecordsByFilename[$e->filename][] = $e;
                }

                $this->components->twoColumnDetail($description, '<fg=yellow;options=bold>MISSING</>');

                continue;
            } catch (AssetDisallowedExtensionException|AssetNotIndexableException) {
                $this->components->twoColumnDetail($description, '<fg=yellow;options=bold>SKIPPED</>');

                continue;
            }

            $this->components->twoColumnDetail($description, '<fg=green;options=bold>DONE</>');
        }

        $this->newLine();

        if ($this->createMissingAssets || $missingRecords->isEmpty()) {
            return $missingRecordsByFilename;
        }

        $this->warn($missingRecords->count() === 1
            ? 'One record is missing:'
            : "{$missingRecords->count()} records are missing:"
        );

        $this->components->bulletList(
            $missingRecords->map(function (MissingAssetException|MissingVolumeFolderException $e) {
                $line = "{$e->volume->name}/{$e->indexEntry->uri}";

                if ($e instanceof MissingVolumeFolderException) {
                    $line .= '/';
                }

                return $line;
            })
                ->all()
        );

        return $missingRecordsByFilename;
    }

    private function processMissingFiles(array $missingFiles, array $missingRecordsByFilename): bool
    {
        $maybes = false;

        $totalMissing = count($missingFiles);

        $this->warn($totalMissing === 1
            ? 'One recorded asset is missing its file:'
            : "$totalMissing recorded assets are missing their files:"
        );

        $this->components->bulletList(collect($missingFiles)->map(function ($filePath, $assetId) use (
            &$maybes,
            $missingRecordsByFilename
        ) {
            $line = "$filePath ($assetId)";
            $filename = basename($filePath);

            if (! isset($missingRecordsByFilename[$filename])) {
                return $line;
            }

            $maybes = true;
            $maybePaths = [];

            foreach ($missingRecordsByFilename[$filename] as $e) {
                /** @var MissingAssetException $e */
                $maybePaths[] = "{$e->volume->name}/{$e->indexEntry->uri}";
            }

            return $line.' (maybe '.implode(', ', $maybePaths).')';
        })->all());

        return $maybes;
    }

    private function fixAssetLocations(
        array $missingFiles,
        array $remainingMissingFiles,
        array $missingRecordsByFilename
    ): array {
        foreach ($missingFiles as $assetId => $filePath) {
            unset($remainingMissingFiles[$assetId]);

            $filename = basename((string) $filePath);

            if (! isset($missingRecordsByFilename[$filename])) {
                continue;
            }

            $e = $this->chooseMissingRecord($filePath, $missingRecordsByFilename[$filename]);
            if (! $e) {
                $this->info("Skipping asset $assetId");

                continue;
            }

            $this->components->task(
                "Relocating asset $assetId to {$e->volume->name}/{$e->indexEntry->uri}",
                function () use ($e, $assetId) {
                    DB::table(Table::ASSETS)
                        ->where('id', $assetId)
                        ->update([
                            'volumeId' => $e->volume->id,
                            'folderId' => $e->folder->id,
                            'dateUpdated' => now(),
                        ]);

                    AssetIndexer::indexFileByEntry($e->indexEntry, $this->cacheRemoteImages, false);
                }
            );
        }

        $this->components->success('Done fixing asset locations.');

        return $remainingMissingFiles;
    }

    /**
     * @param  MissingAssetException[]  $missingRecords
     */
    private function chooseMissingRecord(string $path, array $missingRecords): ?MissingAssetException
    {
        if (count($missingRecords) === 1) {
            // Only one asset with the same name. Probably safe to just go with that.
            return $missingRecords[0];
        }

        $selection = select(
            label: "What is the new location for <fg=cyan>{$path}</>? (leave blank to skip)",
            options: collect($missingRecords)
                ->mapWithKeys(fn ($e, int $i) => [(string) ($i + 1) => "{$e->volume->name}/{$e->indexEntry->uri}"])
                ->all(),
            validate: fn (?string $value) => is_null($value) || (is_numeric($value) && isset($missingRecords[$value - 1])),
            /** @phpstan-ignore-next-line */
            required: false,
        );

        return $selection ? $missingRecords[$selection - 1] : null;
    }
}
