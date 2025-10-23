<?php

namespace CraftCms\Cms\Element\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
}
