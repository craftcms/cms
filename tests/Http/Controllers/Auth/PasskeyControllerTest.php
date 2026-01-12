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
        ->assertJsonValidationErrors(['requestOptions', 'response']);
});

test('login fails with invalid credential', function () {
    postJson(action([PasskeyController::class, 'login']), [
        'requestOptions' => Json::encode(['challenge' => 'test']),
        'response' => Json::encode(['id' => 'non-existent-credential-id']),
    ])->assertStatus(400);
});
