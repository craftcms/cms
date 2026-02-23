<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;

final class AssetIndexingSession extends BaseModel
{
    use HasUid;

    #[\Override]
    protected $table = Table::ASSETINDEXINGSESSIONS;

    #[\Override]
    protected function casts(): array
    {
        return [
            'totalEntries' => 'int',
            'processedEntries' => 'int',
            'cacheRemoteImages' => 'bool',
            'listEmptyFolders' => 'bool',
            'isCli' => 'bool',
            'actionRequired' => 'bool',
            'processIfRootEmpty' => 'bool',
        ];
    }
}
