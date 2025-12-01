<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
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

    protected function casts(): array
    {
        return [
            'enabled' => 'bool',
            'archived' => 'bool',
            'deletedWithOwner' => 'bool',
        ];
    }

    /**
     * @return BelongsToMany<Site, $this, ElementSiteSettings>
     */
    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(
            related: Site::class,
            table: Table::ELEMENTS_SITES,
            foreignPivotKey: 'elementId',
            relatedPivotKey: 'siteId',
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
     * @return BelongsTo<Element, $this>
     */
    public function canonical(): BelongsTo
    {
        return $this->belongsTo(Element::class, 'canonicalId');
    }

    /**
     * @return BelongsTo<Draft, $this>
     */
    public function draft(): BelongsTo
    {
        return $this->belongsTo(Draft::class, 'draftId');
    }

    /**
     * @return HasMany<Draft, $this>
     */
    public function drafts(): HasMany
    {
        return $this->hasMany(Draft::class, 'canonicalId');
    }

    /**
     * @return BelongsTo<Revision, $this>
     */
    public function revision(): BelongsTo
    {
        return $this->belongsTo(Revision::class, 'revisionId');
    }

    /**
     * @return HasMany<Revision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(Revision::class, 'canonicalId');
    }

    /**
     * @return BelongsTo<FieldLayout, $this>
     */
    public function fieldLayout(): BelongsTo
    {
        return $this->belongsTo(FieldLayout::class, 'fieldLayoutId');
    }
}
