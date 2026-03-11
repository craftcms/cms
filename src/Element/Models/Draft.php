<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Draft extends BaseModel
{
    use HasFactory;

    #[\Override]
    protected $table = Table::DRAFTS;

    #[\Override]
    public $timestamps = false;

    #[\Override]
    protected $casts = [
        'provisional' => 'bool',
        'trackChanges' => 'bool',
        'dateLastMerged' => 'datetime',
        'saved' => 'bool',
    ];

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
    public function canonical(): BelongsTo
    {
        return $this->belongsTo(Element::class, 'canonicalId');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creatorId');
    }
}
