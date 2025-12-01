<?php

declare(strict_types=1);

namespace CraftCms\Cms\RouteToken\Model;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;

final class RouteToken extends BaseModel
{
    use HasUid;

    protected $table = Table::ROUTETOKENS;

    protected $casts = [
        'usageLimit' => 'int',
        'usageCount' => 'int',
        'expiryDate' => 'datetime',
        'route' => 'json',
    ];
}
