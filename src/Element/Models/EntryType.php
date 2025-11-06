<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Shared\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

final class EntryType extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = Table::ENTRYTYPES;

    #[\Override]
    protected function casts(): array
    {
        return [
            'hasTitleField' => 'bool',
            'showSlugField' => 'bool',
            'showStatusField' => 'bool',
        ];
    }

    /**
     * @return BelongsToMany<Section, $this, Pivot>
     */
    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(Section::class, Table::SECTIONS_ENTRYTYPES, 'typeId', 'sectionId')
            ->withPivot('sortOrder', 'name', 'handle', 'description');
    }
}
