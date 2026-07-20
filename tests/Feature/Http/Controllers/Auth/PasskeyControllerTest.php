<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Controllers\Auth\PasskeyController;
use CraftCms\Cms\Support\Json;

use function Pest\Laravel\postJson;

test('requestOptions returns passkey request options', function () {
    postJson(action([PasskeyController::class, 'requestOptions']))
        ->assertOk()
        ->assertJsonStructure(['options']);
});

test('login validates required fields', function () {
    postJson(action([PasskeyController::class, 'login']), [])
        ->assertJsonValidationErrors(['requestOptions', 'authResponse']);
});

test('login validates the auth response JSON shape', function (string $authResponse) {
    postJson(action([PasskeyController::class, 'login']), [
        'requestOptions' => Json::encode(['challenge' => 'test']),
        'authResponse' => $authResponse,
    ])->assertJsonValidationErrors(['authResponse']);
})->with([
    'invalid JSON' => ['invalid'],
    'missing credential ID' => [Json::encode([])],
    'non-string credential ID' => [Json::encode(['id' => []])],
]);

test('login fails with invalid credential', function () {
    postJson(action([PasskeyController::class, 'login']), [
        'requestOptions' => Json::encode(['challenge' => 'test']),
        'authResponse' => Json::encode(['id' => 'non-existent-credential-id']),
    ])->assertStatus(400);
});

test('login is limited to five attempts per minute', function () {
    foreach (range(1, 5) as $attempt) {
        postJson(action([PasskeyController::class, 'login']), [
            'requestOptions' => Json::encode(['challenge' => 'test']),
            'authResponse' => Json::encode(['id' => 'non-existent-credential-id']),
        ])->assertStatus(400);
    }

    postJson(action([PasskeyController::class, 'login']), [
        'requestOptions' => Json::encode(['challenge' => 'test']),
        'authResponse' => Json::encode(['id' => 'non-existent-credential-id']),
    ])->assertTooManyRequests();
});
