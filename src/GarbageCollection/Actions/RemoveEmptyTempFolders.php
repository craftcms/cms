<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\Asset\AssetUploads;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Filesystem\Models\UploadSession;
use CraftCms\Cms\Support\Facades\Folders;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Language\Alias;

/**
 * Find all temp upload folders with no assets in them and remove them.
 */
class RemoveEmptyTempFolders extends GarbageCollectionAction
{
    public function __invoke(): void
    {
        $this->components->task(
            'removing empty temp folders',
            function () {
                $activeUploadFolderIds = UploadSession::query()
                    ->where('handler', AssetUploads::class)
                    ->whereNull('result')
                    ->where('expiresAt', '>', now())
                    ->get(['parameters'])
                    ->pluck('parameters')
                    ->map(fn (array $parameters) => (int) ($parameters['folderId'] ?? 0))
                    ->filter()
                    ->unique()
                    ->values();
                $emptyFolderIds = DB::table(Table::VOLUMEFOLDERS, 'folders')
                    ->leftJoin(new Alias(Table::ASSETS, 'assets'), 'assets.folderId', 'folders.id')
                    ->whereNull(['folders.volumeId', 'assets.id'])
                    ->whereNotNull(['folders.parentId', 'folders.path'])
                    ->when(
                        $activeUploadFolderIds->isNotEmpty(),
                        fn ($query) => $query->whereNotIn('folders.id', $activeUploadFolderIds),
                    )
                    ->pluck('folders.id');

                if ($emptyFolderIds->isNotEmpty()) {
                    Folders::deleteFoldersByIds($emptyFolderIds->all());
                }
            }
        );
    }
}
