<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use CraftCms\Cms\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebAuthn extends BaseModel
{
    use HasFactory;
    use HasUid;

    #[\Override]
    protected $table = Table::WEBAUTHN;

    #[\Override]
    protected $casts = [
        'dateLastUsed' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userId');
    }
}
