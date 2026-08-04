<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

class UserGroup extends BaseModel
{
    use HasFactory;
    use HasUid;

    #[\Override]
    protected $table = Table::USERGROUPS;

    /** @return BelongsToMany<User, $this, Pivot> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, Table::USERGROUPS_USERS, 'groupId', 'userId')
            ->withPivot(['dateCreated', 'dateUpdated', 'uid']);
    }

    /** @return BelongsToMany<UserPermission, $this, Pivot> */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(UserPermission::class, Table::USERPERMISSIONS_USERGROUPS, 'groupId', 'permissionId')
            ->withTimestamps('dateCreated', 'dateUpdated')
            ->withPivot('uid');
    }
}
