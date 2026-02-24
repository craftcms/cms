<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands\Utils;

use Craft;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Facades\Volumes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Language\Alias;

final class DeleteEmptyVolumeFoldersCommand extends Command
{
    use CraftCommand;

    #[\Override]
    protected $signature = 'craft:utils:delete-empty-volume-folders {volume?*}';

    #[\Override]
    protected $description = 'Deletes empty volume folders.';

    #[\Override]
    protected $aliases = ['utils/delete-empty-volume-folders'];

    public function handle(): int
    {
        $query = DB::table(Table::VOLUMEFOLDERS, 'folders')
            ->leftJoin(new Alias(Table::ASSETS, 'assets'), 'assets.folderId', '=', 'folders.id')
            ->leftJoin(new Alias(Table::VOLUMEFOLDERS, 'subfolders'), 'subfolders.parentId', '=', 'folders.id')
            ->whereNull(['assets.id', 'subfolders.id'])
            ->whereNotNull(['folders.parentId', 'folders.path']);

        if ($volumes = $this->argument('volume')) {
            $volumeIds = [];

            foreach ($volumes as $handle) {
                $volume = Volumes::getVolumeByHandle($handle);

                if (! $volume) {
                    $this->components->error("Invalid volume handle: $handle");

                    return self::FAILURE;
                }

                $volumeIds[] = $volume->id;
            }

            $query->whereIn('folders.volumeId', $volumeIds);
        }

        $emptyFolderIds = $query->pluck('folders.id')->all();

        if (empty($emptyFolderIds)) {
            $this->components->success('No empty folders found.');

            return self::SUCCESS;
        }

        $message = sprintf(
            'Deleting %s empty %s',
            count($emptyFolderIds),
            count($emptyFolderIds) === 1 ? 'folder' : 'folders',
        );

        $this->components->task($message, function () use ($emptyFolderIds) {
            Craft::$app->getAssets()->deleteFoldersByIds($emptyFolderIds);
        });

        return self::SUCCESS;
    }
}
