<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Entries;
use CraftCms\Cms\Http\Controllers\Entries\ReassignEntriesModalController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\DomCrawler\Crawler;

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

it('submits the rendered reassignment Form through its namespace', function () {
    $response = postJson(action([ReassignEntriesModalController::class, 'show']), [
        'oldUserIds' => [12, 34],
    ])->assertOk();

    $namespace = $response->json('namespace');
    $crawler = new Crawler('<form>'.$response->json('content').'</form>', 'http://localhost');
    $settings = json_decode($crawler->filter('craft-element-select-input')->attr('settings'), true);

    expect($settings['name'])->toBe("{$namespace}[newUserId]")
        ->and($settings['single'])->toBeTrue()
        ->and($settings['limit'])->toBe(1)
        ->and($settings['criteria']['id'])->toBe(['not', 12, 34]);

    $form = $crawler->filter('form')->form();
    $form->setValues(["{$namespace}[newUserId]" => '56']);

    expect($form->getPhpValues())->toBe([$namespace => [
        'newUserId' => '56',
        'oldUserIds' => ['12', '34'],
        'action' => 'entries/reassign',
    ]]);

    $entries = Mockery::mock(Entries::class);
    $entries->shouldReceive('reassignEntries')->once()->with([12, 34], 56)->andReturn(2);
    app()->instance(Entries::class, $entries);

    postJson(action([ReassignEntriesModalController::class, 'store']), $form->getPhpValues(), [
        'X-Craft-Namespace' => $namespace,
    ])->assertOk()->assertJsonPath('message', 'Entries reassigned.');
});
