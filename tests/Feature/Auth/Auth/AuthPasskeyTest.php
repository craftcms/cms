<?php

declare(strict_types=1);

use JMac\Testing\Matching\Argument;
use JMac\Testing\Double;
use CraftCms\Cms\Auth\AuthMethods;
use CraftCms\Cms\Auth\Enums\AuthError;
use CraftCms\Cms\Auth\Events\UserAuthenticating;
use CraftCms\Cms\Auth\Passkeys\CredentialRepository;
use CraftCms\Cms\Auth\Passkeys\Passkeys;
use CraftCms\Cms\Auth\Passkeys\WebauthnServer;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;
use Symfony\Component\Uid\Uuid;
use Webauthn\CredentialRecord;
use Webauthn\TrustPath\EmptyTrustPath;

beforeEach(function () {
    Cms::config()->isSystemLive = true;
});

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
    Session::put($passkeys->passkeyCredSourceParam, $updatedCredentialSource);

    $credentialRepository = Double::for(CredentialRepository::class);
    $credentialRepository->expects('saveCredentialSource')->with($updatedCredentialSource);

    $webauthnServer = Double::for(WebauthnServer::class);
    $webauthnServer->expects('getCredentialRepository')->returns($credentialRepository);

    $passkeys->expects('verifyPasskey')->with(Argument::type(UserElement::class), $requestOptions, $response)->returns(true);
    $passkeys->expects('webauthnServer')->returns($webauthnServer);

    $result = app(AuthMethods::class)->authenticateWithPasskey($user, $requestOptions, $response);

    expect($result)->toBe($expectedResult);
    expect(app(AuthMethods::class)->authError)->toBe($expectedError);
    expect(Session::has($passkeys->passkeyCredSourceParam))->toBeFalse();
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
    $updatedCredentialSource = authPasskeyCredentialSource('test-credential-id');

    $requestOptions = Json::encode(['challenge' => 'test-challenge']);
    $response = Json::encode(['id' => 'test-credential-id', 'response' => 'invalid-response']);

    $passkeys = mockAuthPasskeys();
    Session::put($passkeys->passkeyCredSourceParam, $updatedCredentialSource);

    $passkeys->expects('verifyPasskey')->returns(false);
    $passkeys->expects('webauthnServer')->never();

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
    $passkeys = Double::for(Passkeys::class)->passthru(new Passkeys('pkCredCreationOptions', 'pkReqOptions', 'pkCredSource'));

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
