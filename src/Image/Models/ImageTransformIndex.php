<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use Override;

final class ImageTransformIndex extends BaseModel
{
    #[Override]
    protected $table = Table::IMAGETRANSFORMINDEX;

    #[Override]
    protected function casts(): array
    {
        return [
            'assetId' => 'int',
            'fileExists' => 'bool',
            'inProgress' => 'bool',
            'error' => 'bool',
            'dateIndexed' => 'datetime',
        ];
    }
}
