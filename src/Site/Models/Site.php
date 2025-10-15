<?php

namespace CraftCms\Cms\Site\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Site extends BaseModel
{
    use SoftDeletes;

    protected $table = Table::SITES;

    protected function casts(): array
    {
        return [
            'primary' => 'boolean',
            'hasUrls' => 'boolean',
        ];
    }

    public function siteGroup(): BelongsTo
    {
        return $this->belongsTo(SiteGroup::class, 'groupId');
    }
}
