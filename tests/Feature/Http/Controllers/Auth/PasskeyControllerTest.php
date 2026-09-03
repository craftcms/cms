<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\Passkeys\Passkeys;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Auth\PasskeyController;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Auth;

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
    ])->assertBadRequest();
});

test('login counts an invalid passkey once', function () {
    Cms::config()->maxInvalidLogins = 10;
    config()->set('auth.providers.users.model', UserModel::class);
    Auth::forgetGuards();

    $credentialId = 'invalid-credential-id';
    $user = UserModel::factory()->withPasskey($credentialId)->create();
    $passkeys = new class extends Passkeys
    {
        #[Override]
        public function verifyPasskey(
            CraftUser $user,
            string $requestOptions,
            string $response,
        ): false {
            return false;
        }
    };

    app()->singleton(Passkeys::class, fn () => $passkeys);

    $this->withSession([
        $passkeys->passkeyRequestOptionsParam => Json::encode(['challenge' => 'test']),
    ])->postJson(action([PasskeyController::class, 'login']), [
        'requestOptions' => Json::encode(['challenge' => 'test']),
        'authResponse' => Json::encode(['id' => $credentialId]),
    ])->assertStatus(400);

    expect(UserModel::findOrFail($user->id)->invalidLoginCount)->toBe(1);
});

test('login is limited to five attempts per minute', function () {
    foreach (range(1, 5) as $attempt) {
        postJson(action([PasskeyController::class, 'login']), [
            'requestOptions' => Json::encode(['challenge' => 'test']),
            'authResponse' => Json::encode(['id' => 'non-existent-credential-id']),
        ])->assertBadRequest();
    }

    postJson(action([PasskeyController::class, 'login']), [
        'requestOptions' => Json::encode(['challenge' => 'test']),
        'authResponse' => Json::encode(['id' => 'non-existent-credential-id']),
    ])->assertTooManyRequests();
});
