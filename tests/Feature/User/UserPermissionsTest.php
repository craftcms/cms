<?php

use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Data\PermissionGroup;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\UserGroup;
use CraftCms\Cms\User\UserPermissions;

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

    $user = \CraftCms\Cms\User\Models\User::factory()->create();

    actingAs($user->asElement());

    expect($this->userPermissions->getAllPermissions()->toArray())
        ->not()
        ->toEqualCanonicalizing($this->userPermissions->getAssignablePermissions()->toArray());
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

    $user = \CraftCms\Cms\User\Models\User::firstOrFail();
    $user->userGroups()->attach($group->id);

    expect($this->userPermissions->getGroupPermissionsByUserId($user->id))->toBeEmpty();

    $this->userPermissions->saveGroupPermissions($group->id, ['accessSiteWhenSystemIsOff']);

    expect($this->userPermissions->getGroupPermissionsByUserId($user->id))->not()->toBeEmpty();
});

test('getPermissionsByUserId & doesUserHavePermission', function () {
    $group = UserGroup::factory()->create();

    $user = \CraftCms\Cms\User\Models\User::firstOrFail();
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
