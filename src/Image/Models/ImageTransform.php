<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use Override;

class ImageTransform extends BaseModel
{
    use HasUid;

    #[Override]
    protected $table = Table::IMAGETRANSFORMS;

    #[Override]
    protected function casts(): array
    {
        return [
            'width' => 'int',
            'height' => 'int',
            'quality' => 'int',
            'upscale' => 'bool',
            'parameterChangeTime' => 'datetime',
        ];
    }
}
