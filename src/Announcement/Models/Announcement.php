<?php

declare(strict_types=1);

namespace CraftCms\Cms\Announcement\Models;

use CraftCms\Cms\Plugin\Models\Plugin;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends BaseModel
{
    use HasFactory;

    public const ?string UPDATED_AT = null;

    #[\Override]
    protected function casts(): array
    {
        return [
            'unread' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userId');
    }

    /** @return BelongsTo<Plugin, $this> */
    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class, 'pluginId');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->where('unread', true)
                ->orWhere('dateRead', '>', now()->subDays(7));
        });
    }
}
