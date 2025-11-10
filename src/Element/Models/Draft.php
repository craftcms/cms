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

    protected $table = Table::DRAFTS;

    public $timestamps = false;

    protected $casts = [
        'provisional' => 'bool',
        'trackChanges' => 'bool',
        'dateLastMerged' => 'datetime',
        'saved' => 'bool',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\CraftCms\Cms\Element\Models\Element, $this>
     */
    public function canonical(): BelongsTo
    {
        return $this->belongsTo(Element::class, 'canonicalId');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\CraftCms\Cms\User\Models\User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creatorId');
    }
}
