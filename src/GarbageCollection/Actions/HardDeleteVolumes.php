<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Hard delete eligible volumes, deleting the folders one by one to avoid nested dependency errors.
 */
final class HardDeleteVolumes extends GarbageCollectionAction
{
    public function __invoke(): void
    {
        if (! $this->shouldHardDelete()) {
            return;
        }

        $this->components->task(
            'deleting trashed volumes and their folders',
            function () {
                $volumeIds = $this->hardDeleteQuery(DB::table(Table::VOLUMES))->pluck('id');
                $folders = DB::table(Table::VOLUMEFOLDERS)
                    ->whereIn('volumeId', $volumeIds)
                    ->select('id', 'path')
                    ->get()
                    ->all();

                usort($folders, fn (stdClass $a, stdClass $b) => (int) (substr_count((string) $a->path, '/') < substr_count((string) $b->path, '/')));

                foreach ($folders as $folder) {
                    DB::table(Table::VOLUMEFOLDERS)->where('id', $folder->id)->delete();
                }

                DB::table(Table::VOLUMES)->whereIn('id', $volumeIds)->delete();
            }
        );
    }
}
