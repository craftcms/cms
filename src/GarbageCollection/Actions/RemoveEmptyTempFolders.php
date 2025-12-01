<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Language\Alias;

/**
 * Find all temp upload folders with no assets in them and remove them.
 */
final class RemoveEmptyTempFolders extends GarbageCollectionAction
{
    public function __invoke(): void
    {
        $this->components->task(
            'removing empty temp folders',
            function () {
                $emptyFolderIds = DB::table(Table::VOLUMEFOLDERS, 'folders')
                    ->leftJoin(new Alias(Table::ASSETS, 'assets'), 'assets.folderId', 'folders.id')
                    ->whereNull(['folders.volumeId', 'assets.id'])
                    ->whereNotNull(['folders.parentId', 'folders.path'])
                    ->pluck('folders.id');

                if ($emptyFolderIds->isNotEmpty()) {
                    \Craft::$app->getAssets()->deleteFoldersByIds($emptyFolderIds->all());
                }
            }
        );
    }
}
