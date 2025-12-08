<?php

declare(strict_types=1);

use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\Utilities\SystemMessagesController;
use CraftCms\Cms\SystemMessage\Models\SystemMessage;
use CraftCms\Cms\User\Elements\User;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::find()->firstOrFail());

    $this->edition = Edition::get();

    Edition::set(Edition::Pro);
});

afterEach(function () {
    Edition::set($this->edition);
});

it('needs authentication for the routes', function (string $method, array $route) {
    auth()->logout();

    $this->$method(action($route))->assertUnauthorized();

    \CraftCms\Cms\User\Models\User::first()->update(['admin' => false]);

    actingAs(User::find()->firstOrFail());

    $this->$method(action($route))->assertForbidden();
})->with([
    ['postJson', [SystemMessagesController::class, 'show']],
    ['postJson', [SystemMessagesController::class, 'store']],
]);

it('can get a modal for a system message', function () {
    postJson(action([SystemMessagesController::class, 'show']), [
        'key' => 'account_activation',
    ])
        ->assertOk()
        ->assertSee(t('account_activation_subject'));
});

it('can store a system message', function () {
    expect(SystemMessage::count())->toBe(0);

    postJson(action([SystemMessagesController::class, 'store']), [
        'key' => 'account_activation',
        'language' => 'en-US',
        'subject' => 'A subject',
        'body' => 'A body',
    ])->assertOk();

    expect(SystemMessage::count())->toBe(1);
});

it('validates when saving', function (array $data, array $errors) {
    postJson(action([SystemMessagesController::class, 'store']), $data)
        ->assertJsonValidationErrors($errors);
})->with([
    [
        'data' => [
            'language' => 'en-US',
            'subject' => 'A subject',
            'body' => 'A body',
        ],
        'errors' => ['key'],
    ],
    [
        'data' => [
            'key' => str_repeat('a', 151),
            'language' => 'en-US',
            'subject' => 'A subject',
            'body' => 'A body',
        ],
        'errors' => ['key'],
    ],
    [
        'data' => [
            'key' => 'account_activation',
            'subject' => 'A subject',
            'body' => 'A body',
        ],
        'errors' => ['language'],
    ],
    [
        'data' => [
            'key' => 'account_activation',
            'language' => 'not-a-language',
            'subject' => 'A subject',
            'body' => 'A body',
        ],
        'errors' => ['language'],
    ],
    [
        'data' => [
            'key' => 'account_activation',
            'language' => 'en-US',
            'body' => 'A body',
        ],
        'errors' => ['subject'],
    ],
    [
        'data' => [
            'key' => 'account_activation',
            'subject' => str_repeat('a', 1001),
            'language' => 'en-US',
            'body' => 'A body',
        ],
        'errors' => ['subject'],
    ],
    [
        'data' => [
            'key' => 'account_activation',
            'subject' => 'A subject',
            'language' => 'en-US',
        ],
        'errors' => ['body'],
    ],
]);
