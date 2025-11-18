<?php

declare(strict_types=1);

namespace CraftCms\Cms\Site\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\Element\Models\ElementSiteSettings;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    /**
     * @return BelongsTo<SiteGroup, $this>
     */
    public function siteGroup(): BelongsTo
    {
        return $this->belongsTo(SiteGroup::class, 'groupId');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\CraftCms\Cms\Element\Models\Element, $this, ElementSiteSettings>
     */
    public function elements(): BelongsToMany
    {
        return $this->belongsToMany(
            related: Element::class,
            table: Table::ELEMENTS_SITES,
            foreignPivotKey: 'siteId',
            relatedPivotKey: 'elementId'
        )->using(ElementSiteSettings::class);
    }

    /**
     * @return HasMany<ElementSiteSettings, $this>
     */
    public function siteSettings(): HasMany
    {
        return $this->hasMany(ElementSiteSettings::class, 'siteId');
    }
}
