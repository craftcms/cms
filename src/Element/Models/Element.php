<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use CraftCms\Cms\Site\Models\Site;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Element extends BaseModel
{
    use HasFactory;
    use HasUid;
    use SoftDeletes;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\CraftCms\Cms\Site\Models\Site, $this, \Illuminate\Database\Eloquent\Relations\Pivot>
     */
    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(
            related: Site::class,
            table: Table::ELEMENTS_SITES,
            foreignPivotKey: 'elementId',
            relatedPivotKey: 'siteId'
        )->using(ElementSiteSettings::class);
    }

    /**
     * @return HasMany<ElementSiteSettings, $this>
     */
    public function siteSettings(): HasMany
    {
        return $this->hasMany(ElementSiteSettings::class, 'elementId');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\CraftCms\Cms\Element\Models\Draft, $this>
     */
    public function draft(): BelongsTo
    {
        return $this->belongsTo(Draft::class, 'draftId');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\CraftCms\Cms\Element\Models\Draft, $this>
     */
    public function drafts(): HasMany
    {
        return $this->hasMany(Draft::class, 'canonicalId');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\CraftCms\Cms\Element\Models\Revision, $this>
     */
    public function revision(): BelongsTo
    {
        return $this->belongsTo(Revision::class, 'revisionId');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\CraftCms\Cms\Element\Models\Revision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(Revision::class, 'canonicalId');
    }
}
