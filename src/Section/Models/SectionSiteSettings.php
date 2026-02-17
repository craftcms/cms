<?php

declare(strict_types=1);

namespace CraftCms\Cms\Section\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use CraftCms\Cms\Site\Models\Site;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SectionSiteSettings extends BaseModel
{
    use HasFactory;
    use HasUid;

    protected $table = Table::SECTIONS_SITES;

    #[\Override]
    protected function casts(): array
    {
        return [
            'hasUrls' => 'boolean',
            'enabledByDefault' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'sectionId');
    }

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'siteId');
    }
}
