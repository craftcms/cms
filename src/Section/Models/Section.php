<?php

declare(strict_types=1);

namespace CraftCms\Cms\Section\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Section\Enums\DefaultPlacement;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use CraftCms\Cms\Structure\Models\Structure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Section extends BaseModel
{
    use HasFactory;
    use HasUid;
    use SoftDeletes;

    protected $table = Table::SECTIONS;

    protected $casts = [
        'previewTargets' => 'array',
        'enableVersioning' => 'boolean',
        'maxAuthors' => 'integer',
        'type' => SectionType::class,
        'defaultPlacement' => DefaultPlacement::class,
        'propagationMethod' => PropagationMethod::class,
    ];

    /**
     * @return BelongsTo<Structure, $this>
     */
    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class, 'structureId');
    }

    /**
     * @return HasMany<SectionSiteSettings, $this>
     */
    public function siteSettings(): HasMany
    {
        return $this->hasMany(SectionSiteSettings::class, 'sectionId');
    }

    /**
     * @return BelongsToMany<EntryType, $this, Pivot>
     */
    public function entryTypes(): BelongsToMany
    {
        return $this->belongsToMany(EntryType::class, 'sections_entrytypes', 'sectionId', 'typeId')
            ->withPivot('sortOrder', 'name', 'handle', 'description');
    }
}
