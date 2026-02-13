<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

final class EntryType extends BaseModel
{
    use HasFactory;
    use HasUid;
    use SoftDeletes;

    protected $table = Table::ENTRYTYPES;

    #[\Override]
    protected function casts(): array
    {
        return [
            'hasTitleField' => 'bool',
            'showSlugField' => 'bool',
            'showStatusField' => 'bool',
            'allowLineBreaksInTitles' => 'bool',
        ];
    }

    /**
     * @return BelongsTo<FieldLayout, $this>
     */
    public function fieldLayout(): BelongsTo
    {
        return $this->belongsTo(FieldLayout::class, 'fieldLayoutId');
    }

    /**
     * @return BelongsToMany<Section, $this, Pivot>
     */
    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(Section::class, Table::SECTIONS_ENTRYTYPES, 'typeId', 'sectionId')
            ->withPivot([
                'sortOrder',
                'name',
                'handle',
                'description',
            ]);
    }
}
