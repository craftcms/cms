<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes files on the trashed asset disk whose assets have been hard-deleted or restored.
 *
 * Files are named after their asset’s ID, so any file whose ID doesn’t belong to an asset that’s still holding its file
 * there is left over.
 *
 * @see GeneralConfig::$trashedAssetDisk
 * @since 6.0.0
 */
class DeleteTrashedAssetFiles extends GarbageCollectionAction
{
    public function __invoke(): void
    {
        $diskName = $this->generalConfig->getTrashedAssetDisk();

        if ($diskName === null) {
            return;
        }

        $this->components->task('deleting leftover trashed asset files', function () use ($diskName) {
            $disk = Storage::disk($diskName);

            collect($disk->files())
                ->mapWithKeys(fn (string $path) => preg_match('/^(\d+)(\.[^\/]*)?$/', $path, $match)
                    ? [$path => (int) $match[1]]
                    : [])
                ->chunk($this->garbageCollection::CHUNK_SIZE)
                ->each(function ($chunk) use ($disk) {
                    $trashedIds = DB::table(Table::ASSETS)
                        ->whereIn('id', $chunk->values()->all())
                        ->where('trashedFileExists', true)
                        ->pluck('id')
                        ->flip();

                    $leftovers = $chunk->reject(fn (int $id) => $trashedIds->has($id))->keys()->all();

                    if ($leftovers !== []) {
                        $disk->delete($leftovers);
                    }
                });
        });
    }
}
