<?php

namespace CraftCms\Cms\Site\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class SiteGroup extends BaseModel
{
    use SoftDeletes;

    protected $table = Table::SITEGROUPS;

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class, 'groupId');
    }
}
