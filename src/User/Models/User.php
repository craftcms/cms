<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Models;

use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\UserGroups;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Override;

class User extends BaseModel
{
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
        'rememberToken',
    ];

    private ?Collection $userGroupData = null;

    protected $casts = [
        'active' => 'bool',
        'pending' => 'bool',
        'locked' => 'bool',
        'suspended' => 'bool',
        'admin' => 'bool',
        'lastLoginDate' => 'datetime',
        'invalidLoginWindowStart' => 'datetime',
        'invalidLoginCount' => 'int',
        'lastInvalidLoginDate' => 'datetime',
        'lockoutDate' => 'datetime',
        'hasDashboard' => 'bool',
        'verificationCodeIssuedDate' => 'datetime',
        'passwordResetRequired' => 'bool',
        'lastPasswordChangeDate' => 'datetime',
    ];

    public function isAdmin(): bool
    {
        return (bool) $this->admin;
    }

    #[Override]
    protected function newBaseQueryBuilder(): Builder
    {
        return parent::newBaseQueryBuilder()
            ->whereExists(
                DB::table(Table::ELEMENTS)
                    ->whereColumn(Table::USERS.'.id', Table::ELEMENTS.'.id')
                    ->whereNull(Table::ELEMENTS.'.dateDeleted')
            );
    }

    public function asElement(): \CraftCms\Cms\User\Elements\User
    {
        $element = new \CraftCms\Cms\User\Elements\User(Arr::except($this->toArray(), [
            'invalidLoginWindowStart',
        ]));

        $element->password = $this->password;

        unset($this->uid);

        return $element;
    }

    /**
     * @return BelongsTo<Element, $this>
     */
    public function element(): BelongsTo
    {
        return $this->belongsTo(Element::class, 'id');
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function photo(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'photoId');
    }

    /**
     * @return BelongsTo<Site, $this>
     */
    public function affiliatedSite(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'affiliatedSiteId');
    }

    /** @return BelongsToMany<UserGroup, $this, Pivot> */
    public function userGroups(): BelongsToMany
    {
        return $this->belongsToMany(UserGroup::class, Table::USERGROUPS_USERS, 'userId', 'groupId')
            ->withPivot(['dateCreated', 'dateUpdated', 'uid']);
    }

    /** @return BelongsToMany<UserPermission, $this, Pivot> */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(UserPermission::class, Table::USERPERMISSIONS_USERS, 'userId', 'permissionId')
            ->withTimestamps('dateCreated', 'dateUpdated')
            ->withPivot('uid');
    }

    /**
     * @return Collection<\CraftCms\Cms\User\Data\UserGroup>
     */
    public function getGroups(): Collection
    {
        if (isset($this->userGroupData)) {
            return $this->userGroupData;
        }

        if (Edition::get() < Edition::Pro || ! isset($this->id)) {
            return collect();
        }

        return $this->userGroupData = UserGroups::getGroupsByUserId($this->id);
    }

    /**
     * Returns whether any properties that affect the user's status have changed.
     */
    public function haveIndexAttributesChanged(): bool
    {
        if (! $this->exists) {
            return false;
        }

        return ! empty(Arr::only($this->getDirty(), [
            'active',
            'email',
            'firstName',
            'fullName',
            'lastLoginDate',
            'lastName',
            'pending',
            'suspended',
            'username',
        ]));
    }
}
