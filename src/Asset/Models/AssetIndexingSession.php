<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;

class AssetIndexingSession extends BaseModel
{
    use HasUid;

    /** @var list<string> */
    public array $skippedEntries = [];

    /** @var array<string, mixed> */
    public array $missingEntries = [];

    public bool $forceStop = false;

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

    /** @return array<string, mixed> */
    #[\Override]
    public function toArray(): array
    {
        return [
            ...parent::toArray(),
            'skippedEntries' => $this->skippedEntries,
            'missingEntries' => $this->missingEntries,
            'forceStop' => $this->forceStop,
        ];
    }
}
