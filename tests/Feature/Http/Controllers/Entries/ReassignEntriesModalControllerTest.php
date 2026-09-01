<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Entries;
use CraftCms\Cms\Http\Controllers\Entries\ReassignEntriesModalController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

it('requires authentication for the modal and store actions', function (string $action) {
    Auth::logout();

    postJson(action([ReassignEntriesModalController::class, $action]), [
        'oldUserIds' => [1],
        'newUserId' => 2,
    ])->assertUnauthorized();
})->with([
    'show' => ['show'],
    'store' => ['store'],
]);

it('requires delete users permission for the modal and store actions', function (string $action) {
    Gate::before(function ($user, string $ability) {
        if ($ability === 'deleteUsers') {
            return false;
        }

        return null;
    });

    postJson(action([ReassignEntriesModalController::class, $action]), [
        'oldUserIds' => [1],
        'newUserId' => 2,
    ])->assertForbidden();
})->with([
    'show' => ['show'],
    'store' => ['store'],
]);

it('validates the modal payload', function (array $payload, array $errors) {
    postJson(action([ReassignEntriesModalController::class, 'show']), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($errors);
})->with([
    'missing old users' => [[], ['oldUserIds']],
    'old users must be an array' => [['oldUserIds' => '1'], ['oldUserIds']],
    'old user IDs must be integers' => [['oldUserIds' => ['invalid']], ['oldUserIds.0']],
]);

it('renders the reassign entries modal', function () {
    $response = postJson(action([ReassignEntriesModalController::class, 'show']), [
        'oldUserIds' => [12, 34],
    ])
        ->assertOk()
        ->assertJsonPath('action', 'entries/reassign')
        ->assertJsonPath('submitButtonLabel', 'Reassign');

    expect($response->json('content'))
        ->toContain('Choose a new author')
        ->toContain('newUserId')
        ->toContain('oldUserIds')
        ->toContain('value="12"')
        ->toContain('value="34"')
        ->toContain('entries/reassign');
});

it('validates the store payload', function (array $payload, array $errors) {
    postJson(action([ReassignEntriesModalController::class, 'store']), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($errors);
})->with([
    'missing old users' => [['newUserId' => 3], ['oldUserIds']],
    'old users must be an array' => [['oldUserIds' => '1', 'newUserId' => 3], ['oldUserIds']],
    'old user IDs must be integers' => [['oldUserIds' => ['invalid'], 'newUserId' => 3], ['oldUserIds.0']],
    'missing new user' => [['oldUserIds' => [1]], ['newUserId']],
    'new user must be an integer' => [['oldUserIds' => [1], 'newUserId' => 'invalid'], ['newUserId']],
]);

it('fails when no new author is selected', function () {
    postJson(action([ReassignEntriesModalController::class, 'store']), [
        'oldUserIds' => [1],
        'newUserId' => 0,
    ])->assertBadRequest()
        ->assertJsonPath('message', 'No new author selected.');
});

it('reassigns entries to the selected author', function (int $count, string $message) {
    $entries = Mockery::mock(Entries::class);
    $entries->shouldReceive('reassignEntries')
        ->once()
        ->with([1, 2], 3)
        ->andReturn($count);

    app()->instance(Entries::class, $entries);

    postJson(action([ReassignEntriesModalController::class, 'store']), [
        'oldUserIds' => ['1', '2'],
        'newUserId' => '3',
    ])
        ->assertOk()
        ->assertJsonPath('message', $message);
})->with([
    'single entry' => [1, 'Entry reassigned.'],
    'multiple entries' => [2, 'Entries reassigned.'],
]);
