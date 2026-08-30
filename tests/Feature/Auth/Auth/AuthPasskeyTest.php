<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\AuthMethods;
use CraftCms\Cms\Auth\Enums\AuthError;
use CraftCms\Cms\Auth\Events\UserAuthenticating;
use CraftCms\Cms\Auth\Passkeys\CredentialRepository;
use CraftCms\Cms\Auth\Passkeys\Passkeys;
use CraftCms\Cms\Auth\Passkeys\WebauthnServer;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Event;
use Symfony\Component\Uid\Uuid;
use Webauthn\CredentialRecord;
use Webauthn\TrustPath\EmptyTrustPath;

test('authenticateWithPasskey enforces user status after a valid response', function (
    array $elementAttributes,
    bool $expectedResult,
    ?AuthError $expectedError,
) {
    $user = User::factory()->withPasskey('valid-credential-id')->create();

    if ($elementAttributes !== []) {
        $user->element->update($elementAttributes);
    }

    $updatedCredentialSource = authPasskeyCredentialSource('valid-credential-id');

    $requestOptions = Json::encode(['challenge' => 'test-challenge']);
    $response = Json::encode([
        'id' => 'valid-credential-id',
        'rawId' => 'valid-credential-id',
        'type' => 'public-key',
        'response' => ['valid-response'],
    ]);

    $passkeys = mockAuthPasskeys();

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
        ->with(Mockery::type(UserElement::class), $requestOptions, $response)
        ->andReturn($updatedCredentialSource);
    $passkeys
        ->shouldReceive('webauthnServer')
        ->once()
        ->andReturn($webauthnServer);

    $result = app(AuthMethods::class)->authenticateWithPasskey($user, $requestOptions, $response);

    expect($result)->toBe($expectedResult);
    expect(app(AuthMethods::class)->authError)->toBe($expectedError);
})->with([
    'active' => [[], true, null],
    'disabled' => [['enabled' => false], false, AuthError::InvalidCredentials],
    'archived' => [['archived' => true], false, AuthError::InvalidCredentials],
]);

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
    $requestOptions = Json::encode(['challenge' => 'test-challenge']);
    $response = Json::encode(['id' => 'test-credential-id', 'response' => 'invalid-response']);

    $passkeys = mockAuthPasskeys();

    $passkeys
        ->shouldReceive('verifyPasskey')
        ->once()
        ->andReturn(false);
    $passkeys->shouldNotReceive('webauthnServer');

    $result = app(AuthMethods::class)->authenticateWithPasskey($user, $requestOptions, $response);

    expect($result)->toBeFalse();
});

test('authenticateWithPasskey does not persist a prior result when a replay is rejected', function () {
    $user = User::factory()->withPasskey('valid-credential-id')->createElement();
    $updatedCredentialSource = authPasskeyCredentialSource('valid-credential-id');
    $requestOptions = Json::encode(['challenge' => 'test-challenge']);
    $response = Json::encode(['id' => 'valid-credential-id', 'response' => 'valid-response']);

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

    $passkeys = mockAuthPasskeys();
    $passkeys
        ->shouldReceive('verifyPasskey')
        ->twice()
        ->andReturn($updatedCredentialSource, false);
    $passkeys
        ->shouldReceive('webauthnServer')
        ->once()
        ->andReturn($webauthnServer);

    $authMethods = app(AuthMethods::class);

    expect($authMethods->authenticateWithPasskey($user, $requestOptions, $response))->toBeTrue();
    expect($authMethods->authenticateWithPasskey($user, $requestOptions, $response))->toBeFalse();
});

test('authenticateWithPasskey keeps credential results scoped to concurrent attempts', function () {
    $firstUser = User::factory()->withPasskey('first-credential-id')->createElement();
    $secondUser = User::factory()->withPasskey('second-credential-id')->createElement();
    $firstCredentialSource = authPasskeyCredentialSource('first-credential-id');
    $secondCredentialSource = authPasskeyCredentialSource('second-credential-id');
    $requestOptions = Json::encode(['challenge' => 'test-challenge']);
    $firstResponse = Json::encode(['id' => 'first-credential-id', 'response' => 'first-response']);
    $secondResponse = Json::encode(['id' => 'second-credential-id', 'response' => 'second-response']);

    $credentialRepository = Mockery::mock(CredentialRepository::class);
    $credentialRepository->shouldReceive('saveCredentialSource')->once()->with($firstCredentialSource);
    $credentialRepository->shouldReceive('saveCredentialSource')->once()->with($secondCredentialSource);

    $webauthnServer = Mockery::mock(WebauthnServer::class);
    $webauthnServer->shouldReceive('getCredentialRepository')->twice()->andReturn($credentialRepository);

    $passkeys = mockAuthPasskeys();
    $passkeys
        ->shouldReceive('verifyPasskey')
        ->twice()
        ->andReturnUsing(function (UserElement $user, string $options, string $response) use (
            $firstResponse,
            $firstCredentialSource,
            $secondCredentialSource,
        ): CredentialRecord {
            if ($response === $firstResponse) {
                Fiber::suspend();

                return $firstCredentialSource;
            }

            return $secondCredentialSource;
        });
    $passkeys->shouldReceive('webauthnServer')->twice()->andReturn($webauthnServer);

    $authMethods = app(AuthMethods::class);

    $firstAttempt = new Fiber(fn () => $authMethods->authenticateWithPasskey($firstUser, $requestOptions, $firstResponse));
    $secondAttempt = new Fiber(fn () => $authMethods->authenticateWithPasskey($secondUser, $requestOptions, $secondResponse));

    $firstAttempt->start();
    $secondAttempt->start();
    $firstAttempt->resume();

    expect($firstAttempt->getReturn())->toBeTrue();
    expect($secondAttempt->getReturn())->toBeTrue();
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
    $passkeys = Mockery::mock(Passkeys::class, ['pkCredCreationOptions', 'pkReqOptions'])->makePartial();

    app()->instance(Passkeys::class, $passkeys);
    app()->forgetInstance(AuthMethods::class);

    return $passkeys;
}

function authPasskeyCredentialSource(string $credentialId): CredentialRecord
{
    return CredentialRecord::create(
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
