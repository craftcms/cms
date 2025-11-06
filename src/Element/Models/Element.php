<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use CraftCms\Cms\Site\Models\Site;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Element extends BaseModel
{
    use HasFactory;
    use HasUid;
    use SoftDeletes;

    /**
     * @return BelongsToMany<Site, $this, Pivot>
     */
    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(Site::class, Table::ELEMENTS_SITES, 'elementId', 'siteId')
            ->withPivot([
                'title',
                'slug',
                'uri',
                'content',
                'enabled',
                'dateCreated',
                'dateUpdated',
                'uid',
            ]);
    }
}
