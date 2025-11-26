<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;

final class ImageTransform extends BaseModel
{
    use HasUid;

    protected $table = Table::IMAGETRANSFORMS;

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
