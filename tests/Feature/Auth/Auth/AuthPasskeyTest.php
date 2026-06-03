<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\AuthMethods;
use CraftCms\Cms\Auth\Enums\AuthError;
use CraftCms\Cms\Auth\Events\UserAuthenticating;
use CraftCms\Cms\Auth\Passkeys\CredentialRepository;
use CraftCms\Cms\Auth\Passkeys\Passkeys;
use CraftCms\Cms\Auth\Passkeys\WebauthnServer;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;
use Symfony\Component\Uid\Uuid;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\TrustPath\EmptyTrustPath;

beforeEach(function () {
    Cms::config()->isSystemLive = true;
});

test('authenticateWithPasskey with valid response', function () {
    $user = User::factory()->withPasskey('valid-credential-id')->createElement();
    $updatedCredentialSource = authPasskeyCredentialSource('valid-credential-id');

    $requestOptions = Json::encode(['challenge' => 'test-challenge']);
    $response = Json::encode([
        'id' => 'valid-credential-id',
        'rawId' => 'valid-credential-id',
        'type' => 'public-key',
        'response' => ['valid-response'],
    ]);

    $passkeys = mockAuthPasskeys();
    Session::put($passkeys->passkeyCredSourceParam, $updatedCredentialSource);

    $credentialRepository = Mockery::mock(CredentialRepository::class);
    $credentialRepository
        ->shouldReceive('saveCredentialSource')
        ->once()
        ->with($updatedCredentialSource);

    $webauthnServer = Mockery::mock(WebauthnServer::class);
    $webauthnServer
        ->shouldReceive('getCredentialRepository')
        ->once()
        ->andReturn($credentialRepository);

    $passkeys
        ->shouldReceive('verifyPasskey')
        ->once()
        ->with($user, $requestOptions, $response)
        ->andReturn(true);
    $passkeys
        ->shouldReceive('webauthnServer')
        ->once()
        ->andReturn($webauthnServer);

    $result = app(AuthMethods::class)->authenticateWithPasskey($user, $requestOptions, $response);

    expect($result)->toBeTrue();
    expect(app(AuthMethods::class)->authError)->toBeNull();
    expect(Session::has($passkeys->passkeyCredSourceParam))->toBeFalse();
});

test('authenticateWithPasskey with mismatched credential', function () {
    $user = User::factory()->withPasskey('user-credential-id')->createElement();

    $requestOptions = Json::encode(['challenge' => 'test-challenge']);
    $response = Json::encode(['id' => 'different-credential-id', 'response' => 'response']);

    $result = app(AuthMethods::class)->authenticateWithPasskey($user, $requestOptions, $response);

    expect($result)->toBeFalse();
    expect(app(AuthMethods::class)->authError)->toBe(AuthError::InvalidCredentials);
});

test('authenticateWithPasskey with invalid response', function () {
    $user = User::factory()->withPasskey('test-credential-id')->createElement();
    $updatedCredentialSource = authPasskeyCredentialSource('test-credential-id');

    $requestOptions = Json::encode(['challenge' => 'test-challenge']);
    $response = Json::encode(['id' => 'test-credential-id', 'response' => 'invalid-response']);

    $passkeys = mockAuthPasskeys();
    Session::put($passkeys->passkeyCredSourceParam, $updatedCredentialSource);

    $passkeys
        ->shouldReceive('verifyPasskey')
        ->once()
        ->andReturn(false);
    $passkeys->shouldNotReceive('webauthnServer');

    $result = app(AuthMethods::class)->authenticateWithPasskey($user, $requestOptions, $response);

    expect($result)->toBeFalse();
    expect(Session::has($passkeys->passkeyCredSourceParam))->toBeFalse();
});

test('authenticateWithPasskey with user without passkeys', function () {
    $user = User::factory()->createElement();

    $requestOptions = Json::encode(['challenge' => 'test-challenge']);
    $response = Json::encode(['id' => 'nonexistent-credential', 'response' => 'response']);

    $result = app(AuthMethods::class)->authenticateWithPasskey($user, $requestOptions, $response);

    expect($result)->toBeFalse();
    expect(app(AuthMethods::class)->authError)->toBe(AuthError::InvalidCredentials);
});

test('authenticateWithPasskey event blocking', function () {
    $user = User::factory()->withPasskey('valid-credential-id')->createElement();

    Event::listen(UserAuthenticating::class, function (UserAuthenticating $event) {
        $event->authError = AuthError::InvalidCredentials;
    });

    $requestOptions = Json::encode(['challenge' => 'test-challenge']);
    $response = Json::encode(['id' => 'valid-credential-id', 'response' => 'valid-response']);

    $result = app(AuthMethods::class)->authenticateWithPasskey($user, $requestOptions, $response);

    expect($result)->toBeFalse();
    expect(app(AuthMethods::class)->authError)->toBe(AuthError::InvalidCredentials);
});

test('authenticateWithPasskey event can skip verification', function () {
    $user = User::factory()->createElement();

    Event::listen(UserAuthenticating::class, function (UserAuthenticating $event) {
        $event->performAuthentication = false;
    });

    $requestOptions = Json::encode(['challenge' => 'test-challenge']);
    $response = Json::encode(['id' => 'any-credential', 'response' => 'response']);

    $result = app(AuthMethods::class)->authenticateWithPasskey($user, $requestOptions, $response);

    expect($result)->toBeTrue();
});

function mockAuthPasskeys(): Passkeys
{
    $passkeys = Mockery::mock(Passkeys::class, ['pkCredCreationOptions', 'pkReqOptions', 'pkCredSource'])->makePartial();

    app()->instance(Passkeys::class, $passkeys);
    app()->forgetInstance(AuthMethods::class);

    return $passkeys;
}

function authPasskeyCredentialSource(string $credentialId): PublicKeyCredentialSource
{
    return PublicKeyCredentialSource::create(
        publicKeyCredentialId: $credentialId,
        type: 'public-key',
        transports: [],
        attestationType: 'none',
        trustPath: EmptyTrustPath::create(),
        aaguid: Uuid::v4(),
        credentialPublicKey: 'credential-public-key',
        userHandle: 'user-handle',
        counter: 1,
    );
}
