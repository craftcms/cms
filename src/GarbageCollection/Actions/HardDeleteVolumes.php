<?php

namespace CraftCms\Cms\GarbageCollection\Actions;

use craft\records\Volume;
use craft\records\VolumeFolder;
use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\DB;

/**
 * Hard delete eligible volumes, deleting the folders one by one to avoid nested dependency errors.
 */
final class HardDeleteVolumes extends GarbageCollectionAction
{
    public function run(): void
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

                usort($folders, fn ($a, $b) => substr_count($a['path'], '/') < substr_count($b['path'], '/'));

                foreach ($folders as $folder) {
                    VolumeFolder::deleteAll(['id' => $folder['id']]);
                }

                Volume::deleteAll(['id' => $volumeIds]);
            }
        );
    }
}
