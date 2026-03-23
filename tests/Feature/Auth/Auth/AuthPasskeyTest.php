<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\Auth;
use CraftCms\Cms\Auth\Enums\AuthError;
use CraftCms\Cms\Auth\Events\Authenticating;
use CraftCms\Cms\Auth\Passkeys\Passkeys;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Cms::config()->isSystemLive = true;
});

test('authenticateWithPasskey with valid response', function () {
    $user = User::factory()->withPasskey('valid-credential-id')->createElement();

    $requestOptions = Json::encode(['challenge' => 'test-challenge']);
    $response = Json::encode([
        'id' => 'valid-credential-id',
        'rawId' => 'valid-credential-id',
        'type' => 'public-key',
        'response' => ['valid-response'],
    ]);

    $this->mock(Passkeys::class)
        ->shouldReceive('verifyPasskey')
        ->with($user, $requestOptions, $response)
        ->andReturn(true);

    app()->forgetInstance(Auth::class);

    $result = app(Auth::class)->authenticateWithPasskey($user, $requestOptions, $response);

    expect($result)->toBeTrue();
    expect(app(Auth::class)->authError)->toBeNull();
});

test('authenticateWithPasskey with mismatched credential', function () {
    $user = User::factory()->withPasskey('user-credential-id')->createElement();

    $requestOptions = Json::encode(['challenge' => 'test-challenge']);
    $response = Json::encode(['id' => 'different-credential-id', 'response' => 'response']);

    $result = app(Auth::class)->authenticateWithPasskey($user, $requestOptions, $response);

    expect($result)->toBeFalse();
    expect(app(Auth::class)->authError)->toBe(AuthError::InvalidCredentials);
});

test('authenticateWithPasskey with invalid response', function () {
    $user = User::factory()->withPasskey('test-credential-id')->createElement();

    $requestOptions = Json::encode(['challenge' => 'test-challenge']);
    $response = Json::encode(['id' => 'test-credential-id', 'response' => 'invalid-response']);

    $this->mock(Passkeys::class)
        ->shouldReceive('verifyPasskey')
        ->andReturn(false);

    app()->forgetInstance(Auth::class);

    $result = app(Auth::class)->authenticateWithPasskey($user, $requestOptions, $response);

    expect($result)->toBeFalse();
});

test('authenticateWithPasskey with user without passkeys', function () {
    $user = User::factory()->createElement();

    $requestOptions = Json::encode(['challenge' => 'test-challenge']);
    $response = Json::encode(['id' => 'nonexistent-credential', 'response' => 'response']);

    $result = app(Auth::class)->authenticateWithPasskey($user, $requestOptions, $response);

    expect($result)->toBeFalse();
    expect(app(Auth::class)->authError)->toBe(AuthError::InvalidCredentials);
});

test('authenticateWithPasskey event blocking', function () {
    $user = User::factory()->withPasskey('valid-credential-id')->createElement();

    Event::listen(Authenticating::class, function (Authenticating $event) {
        $event->authError = AuthError::InvalidCredentials;
    });

    $requestOptions = Json::encode(['challenge' => 'test-challenge']);
    $response = Json::encode(['id' => 'valid-credential-id', 'response' => 'valid-response']);

    $result = app(Auth::class)->authenticateWithPasskey($user, $requestOptions, $response);

    expect($result)->toBeFalse();
    expect(app(Auth::class)->authError)->toBe(AuthError::InvalidCredentials);
});

test('authenticateWithPasskey event can skip verification', function () {
    $user = User::factory()->createElement();

    Event::listen(Authenticating::class, function (Authenticating $event) {
        $event->performAuthentication = false;
    });

    $requestOptions = Json::encode(['challenge' => 'test-challenge']);
    $response = Json::encode(['id' => 'any-credential', 'response' => 'response']);

    $result = app(Auth::class)->authenticateWithPasskey($user, $requestOptions, $response);

    expect($result)->toBeTrue();
});
