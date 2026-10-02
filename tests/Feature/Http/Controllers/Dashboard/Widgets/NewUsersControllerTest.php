<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Controllers\Dashboard\Widgets\NewUsersController;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\Models\UserGroup;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\freezeTime;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(UserElement::find()->one());
});

it('requires authentication', function () {
    auth()->logout();
    postJson(action([NewUsersController::class, 'data']))->assertUnauthorized();
});

it('validates required fields', function () {
    postJson(action([NewUsersController::class, 'data']), [])
        ->assertJsonValidationErrors(['startDate', 'endDate']);
});

it('validates integer fields', function () {
    postJson(action([NewUsersController::class, 'data']), [
        'startDate' => 'not-an-integer',
        'endDate' => 'not-an-integer',
        'userGroupId' => 'not-an-integer',
    ])->assertJsonValidationErrors(['startDate', 'endDate', 'userGroupId']);
});

it('returns chart data', function () {
    $startDate = now()->subDays(7);
    $endDate = now();

    postJson(action([NewUsersController::class, 'data']), [
        'startDate' => $startDate->timestamp,
        'endDate' => $endDate->timestamp,
    ])->assertOk()
        ->assertJsonStructure([
            'dataTable' => [
                'columns',
                'rows',
            ],
            'total',
            'formats',
            'orientation',
            'scale',
        ]);
});

it('filters by user group', function () {
    $startDate = now()->subDays(7);
    $endDate = now();

    $group = UserGroup::factory()->create();
    $userInGroup = UserModel::factory()->active()->create()->asElement();
    UserModel::factory()->active()->create()->asElement();

    // Assign user to group
    Users::assignUserToGroups($userInGroup->id, [$group->id]);

    // Request data for the group
    postJson(action([NewUsersController::class, 'data']), [
        'startDate' => $startDate->timestamp,
        'endDate' => $endDate->timestamp,
        'userGroupId' => $group->id,
    ])->assertOk()
        ->assertJsonPath('total', 1);

    // Request data for a different group ID (should be 0)
    postJson(action([NewUsersController::class, 'data']), [
        'startDate' => $startDate->timestamp,
        'endDate' => $endDate->timestamp,
        'userGroupId' => $group->id + 1,
    ])->assertOk()
        ->assertJsonPath('total', 0);
});

it('returns correct total', function () {
    freezeTime();

    $startDate = now()->subDays(1);
    $endDate = now();

    // Create users within the date range to assert on a known count
    UserModel::factory()->active()->count(3)->create();

    postJson(action([NewUsersController::class, 'data']), [
        'startDate' => $startDate->timestamp,
        'endDate' => $endDate->timestamp,
    ])->assertOk()
        ->assertJsonPath('total', 4); // 3 new + 1 admin user from Install
});
