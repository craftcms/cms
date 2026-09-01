<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Models;

use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Auth\Concerns\ConfirmsPasswords;
use CraftCms\Cms\Database\Factories\UserFactory;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\User\Concerns\CraftUserTrait;
use CraftCms\Cms\User\Contracts\CraftUser;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Override;

#[Hidden([
    'password',
    'rememberToken',
])]
class User extends BaseModel implements CraftUser
{
    use Authenticatable;
    use Authorizable;
    use CanResetPassword, CraftUserTrait {
        CraftUserTrait::sendPasswordResetNotification insteadof CanResetPassword;
    }
    use ConfirmsPasswords;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;

    #[Override]
    public $incrementing = false;

    #[Override]
    protected $with = [
        'element',
    ];

    #[Override]
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
        'passwordResetRequired' => 'bool',
        'lastPasswordChangeDate' => 'datetime',
    ];

    protected function getNameAttribute(): string
    {
        return $this->defaultName();
    }

    protected function getFriendlyNameAttribute(): ?string
    {
        return $this->defaultFriendlyName();
    }

    protected function getUidAttribute(): ?string
    {
        if (! $this->id) {
            return null;
        }

        return $this->relationLoaded('element')
            ? $this->element?->uid
            : $this->element()->value('uid');
    }

    protected function setUidAttribute(?string $value): void {}

    public function haveIndexAttributesChanged(): bool
    {
        return $this->isDirty([
            'username',
            'fullName',
            'firstName',
            'lastName',
            'email',
            'admin',
            'active',
            'pending',
            'locked',
            'suspended',
            'lastLoginDate',
            'photoId',
        ]);
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
            'element',
            'invalidLoginWindowStart',
        ]));

        if ($this->element) {
            $element->enabled = $this->element->enabled;
            $element->archived = $this->element->archived;
        }

        $element->password = $this->password;

        if ($this->id && ! $element->uid) {
            $element->uid = $this->relationLoaded('element')
                ? $this->element?->uid
                : $this->element()->value('uid');
        }

        return $element;
    }

    #[Override]
    public function getRememberTokenName(): string
    {
        return 'rememberToken';
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
}
