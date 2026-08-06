<?php

declare(strict_types=1);

namespace CraftCms\Cms\Structure\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use CraftCms\Cms\Structure\Concerns\StructureNode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StructureElement extends BaseModel
{
    /** @use HasFactory<Factory<StructureElement>> */
    use HasFactory;

    use HasUid;
    use StructureNode;

    #[\Override]
    protected $table = Table::STRUCTUREELEMENTS;

    /**
     * @return BelongsTo<Structure, $this>
     */
    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class, 'structureId');
    }

    /**
     * @return BelongsTo<Element, $this>
     */
    public function element(): BelongsTo
    {
        return $this->belongsTo(Element::class, 'elementId');
    }
}
