<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecoveryCodes extends BaseModel
{
    #[\Override]
    protected $table = Table::RECOVERYCODES;

    #[\Override]
    protected $casts = [
        'recoveryCodes' => 'array',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userId');
    }
}
