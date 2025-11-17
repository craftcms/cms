<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Models;

use Craft;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Shared\BaseModel;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Support\Collection;
use Override;

class User extends BaseModel implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    use Authenticatable;
    use Authorizable;
    use CanResetPassword;
    use HasFactory;
    use MustVerifyEmail;

    public $incrementing = false;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'admin' => 'boolean',
    ];

    private ?Collection $userGroupData = null;

    public function isAdmin(): bool
    {
        return $this->admin;
    }

    /**
     * Returns whether the user has permission to perform a given action.
     *
     * @param  string  $abilities
     *
     * @todo Permissions to Laravel Gates
     */
    #[Override]
    public function can($abilities, $arguments = []): bool
    {
        if (
            $this->admin ||
            Edition::get() === Edition::Solo
        ) {
            return true;
        }

        if (! isset($this->id)) {
            return false;
        }

        return Craft::$app->getUserPermissions()->doesUserHavePermission($this->id, $abilities);
    }

    /** @return BelongsToMany<UserGroup, $this, Pivot> */
    public function userGroups(): BelongsToMany
    {
        return $this->belongsToMany(UserGroup::class, Table::USERGROUPS_USERS, 'userId', 'groupId')
            ->withPivot(['dateCreated', 'dateUpdated', 'uid']);
    }

    /**
     * @return Collection<\craft\models\UserGroup>
     */
    public function getGroups(): Collection
    {
        if (isset($this->userGroupData)) {
            return $this->userGroupData;
        }

        if (Edition::get() < Edition::Pro || ! isset($this->id)) {
            return collect();
        }

        return $this->userGroupData = collect(Craft::$app->getUserGroups()->getGroupsByUserId($this->id));
    }
}
