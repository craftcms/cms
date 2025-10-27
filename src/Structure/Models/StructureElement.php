<?php

declare(strict_types=1);

namespace CraftCms\Cms\Structure\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StructureElement extends BaseModel
{
    use HasUid;

    protected $table = Table::STRUCTUREELEMENTS;

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class, 'structureId');
    }

    public function element(): BelongsTo
    {
        return $this->belongsTo(Element::class, 'elementId');
    }
}
