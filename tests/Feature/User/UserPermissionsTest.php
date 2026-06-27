<?php

use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Data\PermissionGroup;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\UserGroup;
use CraftCms\Cms\User\UserPermissions;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Edition::set(Edition::Pro);

    $this->userPermissions = app(UserPermissions::class);
});

test('getAllPermissions', function () {
    expect($this->userPermissions->getAllPermissions())->not()->toBeEmpty();
});

test('getAllPermissions contains headings', function (string $heading) {
    if (str_contains($heading, 'Pages')) {
        Section::factory()->create(['name' => 'Pages']);
    }

    if (str_contains($heading, 'Assets')) {
        Volume::factory()->create(['name' => 'Assets']);
    }

    if ($heading === 'Sites') {
        Site::factory()->create();
        Sites::refreshSites();
    }

    expect($this->userPermissions->getAllPermissions()->firstWhere('heading', $heading))->toBeInstanceOf(PermissionGroup::class);
})->with([
    'General',
    'Users',
    'Sites',
    'Section - Pages',
    'Volume - Assets',
    'Utilities',
]);

test('getAssignablePermissions', function () {
    $admin = User::find()->one();

    actingAs($admin);

    expect($this->userPermissions->getAllPermissions()->toArray())
        ->toEqualCanonicalizing($this->userPermissions->getAssignablePermissions()->toArray());

    $user = CraftCms\Cms\User\Models\User::factory()->create();

    actingAs($user->asElement());

    expect($this->userPermissions->getAllPermissions()->toArray())
        ->not()
        ->toEqualCanonicalizing($this->userPermissions->getAssignablePermissions()->toArray());
});

test('getAssignablePermissions includes existing recipient permissions without a current user', function () {
    auth()->logout();

    $recipient = CraftCms\Cms\User\Models\User::factory()->create();
    $this->userPermissions->saveUserPermissions($recipient->id, ['accessSiteWhenSystemIsOff']);

    $permissions = $this->userPermissions
        ->getAssignablePermissions($recipient->asElement())
        ->flatMap(fn (PermissionGroup $group) => $group->permissions)
        ->pluck('key');

    expect($permissions)->toContain('accessSiteWhenSystemIsOff');
});

test('getPermissionsByGroupId & doesGroupHavePermission', function () {
    $group = UserGroup::factory()->create();

    expect($this->userPermissions->getPermissionsByGroupId(999))->toBeEmpty();
    expect($this->userPermissions->getPermissionsByGroupId($group->id))->toBeEmpty();

    expect($this->userPermissions->doesGroupHavePermission($group->id, 'accessSiteWhenSystemIsOff'))->toBeFalse();

    $this->userPermissions->saveGroupPermissions($group->id, ['accessSiteWhenSystemIsOff']);

    expect($this->userPermissions->getPermissionsByGroupId(999))->toBeEmpty();
    expect($this->userPermissions->getPermissionsByGroupId($group->id))->not()->toBeEmpty();

    expect($this->userPermissions->doesGroupHavePermission($group->id, 'accessSiteWhenSystemIsOff'))->toBeTrue();
});

test('getGroupPermissionsByUserId', function () {
    $group = UserGroup::factory()->create();

    $user = CraftCms\Cms\User\Models\User::firstOrFail();
    $user->userGroups()->attach($group->id);

    expect($this->userPermissions->getGroupPermissionsByUserId($user->id))->toBeEmpty();

    $this->userPermissions->saveGroupPermissions($group->id, ['accessSiteWhenSystemIsOff']);

    expect($this->userPermissions->getGroupPermissionsByUserId($user->id))->not()->toBeEmpty();
});

test('getPermissionsByUserId & doesUserHavePermission', function () {
    $group = UserGroup::factory()->create();

    $user = CraftCms\Cms\User\Models\User::firstOrFail();
    $user->userGroups()->attach($group->id);

    expect($this->userPermissions->getPermissionsByUserId($user->id))->toBeEmpty();
    expect($this->userPermissions->doesUserHavePermission($user->id, 'accessSiteWhenSystemIsOff'))->toBeFalse();

    $this->userPermissions->saveGroupPermissions($group->id, ['accessSiteWhenSystemIsOff']);

    expect($this->userPermissions->getPermissionsByUserId($user->id))->not()->toBeEmpty();
    expect($this->userPermissions->doesUserHavePermission($user->id, 'accessSiteWhenSystemIsOff'))->toBeTrue();
});

test('validatePermission', function () {
    expect($this->userPermissions->validatePermission('invalidPermission'))->toBeFalse();
    expect($this->userPermissions->validatePermission('accessSiteWhenSystemIsOff'))->toBeTrue();
});

test('saveUserPermissions', function () {
    $user = User::find()->one();

    expect($this->userPermissions->doesUserHavePermission($user->id, 'accessSiteWhenSystemIsOff'))->toBeFalse();

    $this->userPermissions->saveUserPermissions($user->id, ['accessSiteWhenSystemIsOff']);

    expect($this->userPermissions->doesUserHavePermission($user->id, 'accessSiteWhenSystemIsOff'))->toBeTrue();
});

test('permissions are resolved with canonical casing', function () {
    $user = User::find()->one();

    $this->userPermissions->saveUserPermissions($user->id, ['accesssitewhensystemisoff']);

    expect($this->userPermissions->getPermissionsByUserId($user->id)->all())
        ->toContain('accessSiteWhenSystemIsOff');
    expect(DB::table(Table::USERPERMISSIONS)->where('name', 'accessSiteWhenSystemIsOff')->exists())
        ->toBeTrue();

    DB::table(Table::USERPERMISSIONS_USERS)
        ->where('userId', $user->id)
        ->delete();

    $now = now();
    $legacyPermissionId = DB::table(Table::USERPERMISSIONS)
        ->where('name', 'accesscp')
        ->value('id') ?? DB::table(Table::USERPERMISSIONS)->insertGetId([
            'name' => 'accesscp',
            'dateCreated' => $now,
            'dateUpdated' => $now,
            'uid' => Str::uuid(),
        ]);

    DB::table(Table::USERPERMISSIONS_USERS)->insert([
        'permissionId' => $legacyPermissionId,
        'userId' => $user->id,
        'dateCreated' => $now,
        'dateUpdated' => $now,
        'uid' => Str::uuid(),
    ]);

    $this->userPermissions->reset();

    expect($this->userPermissions->doesUserHavePermission($user->id, 'accessCp'))->toBeTrue();
    expect($this->userPermissions->getPermissionsByUserId($user->id)->all())
        ->toContain('accessCp');
});
