<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

final class UserPermission extends BaseModel
{
    use HasFactory;
    use HasUid;

    #[\Override]
    protected $table = Table::USERPERMISSIONS;

    /** @return BelongsToMany<User, $this, Pivot> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, Table::USERPERMISSIONS_USERS, 'permissionId', 'userId')
            ->withTimestamps('dateCreated', 'dateUpdated')
            ->withPivot('uid');
    }

    /** @return BelongsToMany<UserGroup, $this, Pivot> */
    public function userGroups(): BelongsToMany
    {
        return $this->belongsToMany(UserGroup::class, Table::USERPERMISSIONS_USERGROUPS, 'permissionId', 'groupId')
            ->withTimestamps('dateCreated', 'dateUpdated')
            ->withPivot('uid');
    }
}
