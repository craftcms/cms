<?php

namespace CraftCms\Cms\Section\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Section\Enums\DefaultPlacement;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use CraftCms\Cms\Structure\Models\Structure;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Section extends BaseModel
{
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

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class, 'structureId');
    }

    public function siteSettings(): HasMany
    {
        return $this->hasMany(SectionSiteSettings::class, 'sectionId');
    }
}
