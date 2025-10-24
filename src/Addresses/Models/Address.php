<?php

declare(strict_types=1);

namespace CraftCms\Cms\Addresses\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Address extends BaseModel
{
    protected $table = Table::ADDRESSES;

    /** @return BelongsTo<User, $this> */
    public function primaryOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'primaryOwnerId');
    }
}
