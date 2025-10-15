<?php

namespace CraftCms\Cms\Site\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Site extends BaseModel
{
    use HasFactory;
    use HasUid;
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
