<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace Craft\Cms\Announcement\Models;

use Craft\Cms\Plugin\Models\Plugin;
use Craft\Cms\Support\BaseModel;
use Craft\Cms\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Date;

class Announcement extends BaseModel
{
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
