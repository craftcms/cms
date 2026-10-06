<?php

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\UserGroup as UserGroupModel;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    DB::table(Table::USERGROUPS)->delete();

    Edition::set(Edition::Pro);

    UserGroups::saveGroup($group = new UserGroup([
        'name' => 'Test group',
        'handle' => 'testGroup',
    ]));

    $this->group = $group;
});

it('can get all groups', function () {
    // Empty when solo
    Edition::set(Edition::Solo);
    expect(UserGroups::getAllGroups())->toBeEmpty();

    // TeamGroup when Team
    Edition::set(Edition::Team);
    expect(UserGroups::getAllGroups())->toHaveCount(1);
    expect(UserGroups::getAllGroups()->first()->name)->toBe('Team');

    // All groups when Pro
    Edition::set(Edition::Pro);
    expect(UserGroups::getAllGroups())->toHaveCount(2); // Initial + automatically created Team group
});

it('can get assignable groups', function () {
    // Empty when not logged in
    expect(UserGroups::getAssignableGroups())->toBeEmpty();

    $recipient = CraftCms\Cms\User\Models\User::factory()->createElement();
    $recipient->setGroups([$this->group]);

    expect(UserGroups::getAssignableGroups($recipient)->pluck('id'))->toContain($this->group->id);

    // All when admin
    actingAs(User::find()->admin()->one());
    expect(UserGroups::getAssignableGroups()->count())->toBeGreaterThan(0);

    // No group when user has no permissions to assign groups
    actingAs(CraftCms\Cms\User\Models\User::factory()->createElement());
    expect(UserGroups::getAssignableGroups())->toBeEmpty();
});

it('can get a group by id', function () {
    expect(UserGroups::getGroupById($this->group->id))->not()->toBeNull();
});

it('can get a group by uid', function () {
    expect(UserGroups::getGroupByUid($this->group->uid))->not()->toBeNull();
});

it('can get a group by handle', function () {
    expect(UserGroups::getGroupByHandle($this->group->handle))->not()->toBeNull();
});

it('does not save invalid groups', function (array $attributes, array $expectedErrors) {
    $group = new UserGroup($attributes);
    $groupCount = UserGroupModel::count();

    expect(UserGroups::saveGroup($group))->toBeFalse()
        ->and($group->errors()->keys())->toEqualCanonicalizing($expectedErrors)
        ->and($group->id)->toBeNull()
        ->and($group->uid)->toBeNull()
        ->and(UserGroupModel::count())->toBe($groupCount);
})->with([
    'required values' => [[], ['name', 'handle']],
    'unique values' => [[
        'name' => 'Test group',
        'handle' => 'testGroup',
    ], ['name', 'handle']],
]);

it('can skip validation when saving a group', function () {
    $group = new UserGroup([
        'name' => 'Test group',
        'handle' => 'testGroup',
    ]);

    expect(UserGroups::saveGroup($group, runValidation: false))->toBeTrue()
        ->and($group->id)->not()->toBeNull()
        ->and($group->uid)->not()->toBeNull();
});

it('can get team group', function () {
    Edition::set(Edition::Team);

    expect(UserGroups::getTeamGroup())->not()->toBeNull();
});

it('saves changes to the team group without changing its identity', function () {
    Edition::set(Edition::Team);
    $group = UserGroups::getTeamGroup();
    $id = $group->id;
    $uid = $group->uid;
    $group->description = 'Updated description';

    expect(UserGroups::saveGroup($group))->toBeTrue()
        ->and($group->id)->toBe($id)
        ->and($group->uid)->toBe($uid)
        ->and(UserGroups::getGroupByUid($uid)->description)->toBe('Updated description');
});

it('creates a unique name and handle for the team group', function () {
    UserGroups::saveGroup(new UserGroup([
        'name' => 'Team',
        'handle' => 'team',
    ]));

    Edition::set(Edition::Team);

    expect(UserGroups::getTeamGroup()->name)->toBe('Team 2');
    expect(UserGroups::getTeamGroup()->handle)->toBe('team2');
});

it('can get groups by user id', function () {
    expect(UserGroups::getGroupsByUserId(User::find()->one()->id))->toBeEmpty();

    CraftCms\Cms\User\Models\User::firstOrFail()->userGroups()->attach($this->group->id);

    expect(UserGroups::getGroupsByUserId(User::find()->one()->id))->toHaveCount(1);
});

it('can delete a group by id', function () {
    expect(UserGroupModel::count())->toBe(1);

    UserGroups::deleteGroupById($this->group->id);

    expect(UserGroupModel::count())->toBe(0);
});

it('can delete a group', function () {
    expect(UserGroupModel::count())->toBe(1);

    UserGroups::deleteGroup($this->group);

    expect(UserGroupModel::count())->toBe(0);
});
