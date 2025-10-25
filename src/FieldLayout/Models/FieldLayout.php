<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\EntryType\Models\EntryType;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class FieldLayout extends BaseModel
{
    use HasFactory;
    use HasUid;
    use SoftDeletes;

    protected $table = Table::FIELDLAYOUTS;

    protected $casts = [
        'config' => 'json',
    ];

    public function entryTypes(): HasMany
    {
        return $this->hasMany(EntryType::class, 'fieldLayoutId');
    }
}
