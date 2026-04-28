<?php

declare(strict_types=1);

namespace CraftCms\Cms\RouteToken\Model;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;

class RouteToken extends BaseModel
{
    use HasUid;

    #[\Override]
    protected $table = Table::ROUTETOKENS;

    #[\Override]
    protected $casts = [
        'usageLimit' => 'int',
        'usageCount' => 'int',
        'expiryDate' => 'datetime',
        'route' => 'json',
    ];
}
