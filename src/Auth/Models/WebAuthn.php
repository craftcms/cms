<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use CraftCms\Cms\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WebAuthn extends BaseModel
{
    use HasUid;

    protected $table = Table::WEBAUTHN;

    protected function casts(): array
    {
        return [
            'dateLastUsed' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userId');
    }
}
