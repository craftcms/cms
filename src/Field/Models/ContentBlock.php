<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\Shared\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ContentBlock extends BaseModel
{
    protected $table = Table::CONTENTBLOCKS;

    public $incrementing = false;

    public $timestamps = false;

    /**
     * @return BelongsTo<Element, $this>
     */
    public function element(): BelongsTo
    {
        return $this->belongsTo(Element::class, 'id');
    }

    /**
     * @return BelongsTo<Element, $this>
     */
    public function primaryOwner(): BelongsTo
    {
        return $this->belongsTo(Element::class, 'primaryOwnerId');
    }

    /**
     * @return BelongsTo<Field, $this>
     */
    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class, 'fieldId');
    }
}
