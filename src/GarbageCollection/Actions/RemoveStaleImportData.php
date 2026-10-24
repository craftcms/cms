<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\Import\Jobs\FinishImport;
use Illuminate\Support\Facades\Storage;

/**
 * Removes the items that import runs stored on the import disk, for runs that never got to clean up after themselves.
 *
 * @since 6.0.0
 */
class RemoveStaleImportData extends GarbageCollectionAction
{
    public function __invoke(): void
    {
        $this->components->task('removing stale import data', function () {
            $disk = Storage::disk($this->generalConfig->getImportDisk());
            $cutoff = now()->subDays(7)->getTimestamp();

            foreach ($disk->directories(FinishImport::SPOOL_ROOT) as $directory) {
                $lastModified = collect($disk->allFiles($directory))
                    ->map(fn (string $file) => $disk->lastModified($file))
                    ->max();

                if ($lastModified === null || $lastModified < $cutoff) {
                    $disk->deleteDirectory($directory);
                }
            }
        });
    }
}
