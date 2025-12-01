<?php

declare(strict_types=1);

namespace CraftCms\Cms\Structure\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Structure extends BaseModel
{
    use HasFactory;
    use HasUid;
    use SoftDeletes;

    protected $table = Table::STRUCTURES;

    protected function casts(): array
    {
        return [
            'maxLevels' => 'int',
        ];
    }

    /**
     * @return HasMany<StructureElement, $this>
     */
    public function structureElements(): HasMany
    {
        return $this->hasMany(StructureElement::class, 'structureId');
    }
}
