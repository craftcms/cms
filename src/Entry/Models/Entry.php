<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Shared\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Entry extends BaseModel
{
    use HasFactory;

    protected $table = Table::ENTRIES;

    public $incrementing = false;

    protected $casts = [
        'postDate' => 'datetime',
        'expiryDate' => 'datetime',
        'deletedWithEntryType' => 'boolean',
        'deletedWithSection' => 'boolean',
    ];

    public function element(): BelongsTo
    {
        return $this->belongsTo(Element::class, 'id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'sectionId');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parentId');
    }

    public function primaryOwner(): BelongsTo
    {
        return $this->belongsTo(Element::class, 'primaryOwnerId');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class, 'fieldId');
    }

    public function entryType(): BelongsTo
    {
        return $this->belongsTo(EntryType::class, 'typeId');
    }
}
