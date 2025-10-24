<?php

declare(strict_types=1);

namespace CraftCms\Cms\Site\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class SiteGroup extends BaseModel
{
    use HasFactory;
    use HasUid;
    use SoftDeletes;

    protected $table = Table::SITEGROUPS;

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class, 'groupId');
    }
}
