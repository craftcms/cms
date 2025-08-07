<?php

namespace CraftCms\Cms\Announcement\Models;

use CraftCms\Cms\Plugin\Models\Plugin;
use CraftCms\Cms\Support\BaseModel;
use CraftCms\Cms\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Date;

class Announcement extends BaseModel
{
    use HasFactory;

    public const UPDATED_AT = null;

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
                ->orWhere('dateRead', '>', Date::now()->subDays(7));
        });
    }
}
