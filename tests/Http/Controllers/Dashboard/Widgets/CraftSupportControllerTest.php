<?php

use CraftCms\Cms\Dashboard\Dashboard;
use CraftCms\Cms\Http\Controllers\Dashboard\Widgets\CraftSupportController;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::first());

    $this->dashboard = app(Dashboard::class);
});

it('requires login', function () {
    Auth::logout();

    postJson(action(CraftSupportController::class))
        ->assertUnauthorized();
});

it('validates data', function (array $data, array $errors) {
    Http::fake([
        'https://api.craftcms.com/v1/support' => Http::response([]),
    ]);

    $response = postJson(action(CraftSupportController::class), $data);

    if (count($errors) === 0) {
        $response->assertOk();

        return;
    }

    $response->assertStatus(422);
    $errorKeys = array_keys($response->json('errors'));

    expect($errorKeys)->toMatchArray($errors);
})->with([
    [
        'data' => [
            'fromEmail' => 'support@craftcms.com',
            'message' => 'test',
        ],
        'errors' => [],
    ],
    [
        'data' => [
            'fromEmail' => 'not-an-email',
            'message' => 'test',
        ],
        'errors' => ['fromEmail'],
    ],
    [
        'data' => [
            'fromEmail' => 'support@craftcms.com',
        ],
        'errors' => ['message'],
    ],
    [
        'data' => [
            'fromEmail' => 'support@craftcms.com',
            'message' => 'test',
            'attachLogs' => 'not-a-boolean',
        ],
        'errors' => ['attachLogs'],
    ],
    [
        'data' => [
            'fromEmail' => 'support@craftcms.com',
            'message' => 'test',
            'attachDbBackup' => 'not-a-boolean',
        ],
        'errors' => ['attachDbBackup'],
    ],
    [
        'data' => [
            'fromEmail' => 'support@craftcms.com',
            'message' => 'test',
            'attachTemplates' => 'not-a-boolean',
        ],
        'errors' => ['attachTemplates'],
    ],
]);
