<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\Models\WebAuthn;
use CraftCms\Cms\Auth\Passkeys\CredentialRepository;
use CraftCms\Cms\Auth\Passkeys\Passkeys;
use CraftCms\Cms\Auth\Passkeys\WebauthnServer;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Session;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\TrustPath\EmptyTrustPath;

use function Pest\Laravel\freezeSecond;

beforeEach(function () {
    $this->passkeys = app(Passkeys::class);
    $this->user = UserModel::factory()->active()->createElement();
    $this->user->uid = Str::uuid()->toString();
});

test('hasPasskeys returns false for user with no id', function () {
    $user = UserModel::factory()->make(['id' => null])->asElement();

    expect($this->passkeys->hasPasskeys($user))->toBeFalse();
});

test('hasPasskeys returns false for user with no passkeys', function () {
    expect($this->passkeys->hasPasskeys($this->user))->toBeFalse();
});

test('hasPasskeys returns true for user with passkeys', function () {
    WebAuthn::factory()->create([
        'userId' => $this->user->id,
        'credentialId' => 'test-credential-id',
    ]);

    expect($this->passkeys->hasPasskeys($this->user))->toBeTrue();
});

test('getPasskeys returns empty collection for user with no id', function () {
    $user = UserModel::factory()->make(['id' => null])->asElement();

    expect($this->passkeys->getPasskeys($user))->toBeEmpty();
});

test('getPasskeys returns empty collection for user with no passkeys', function () {
    expect($this->passkeys->getPasskeys($this->user))->toBeEmpty();
});

test('getPasskeys returns collection with passkey data', function () {
    freezeSecond();

    WebAuthn::factory()->create([
        'userId' => $this->user->id,
        'credentialId' => 'test-credential-id',
        'dateLastUsed' => now(),
        'credentialName' => 'My Passkey',
    ]);

    $passkeys = $this->passkeys->getPasskeys($this->user);

    expect($passkeys)->toHaveCount(1);
    expect($passkeys[0])->toMatchArray([
        'credentialName' => 'My Passkey',
        'dateLastUsed' => now(),
    ]);
});

test('getPasskeys returns multiple passkeys', function () {
    WebAuthn::factory(2)->create([
        'userId' => $this->user->id,
    ]);

    $passkeys = $this->passkeys->getPasskeys($this->user);

    expect($passkeys)->toHaveCount(2);
});

test('getPasskeyCreationOptions returns PublicKeyCredentialOptions', function () {
    $options = $this->passkeys->getPasskeyCreationOptions($this->user);

    expect($options)->toBeInstanceOf(PublicKeyCredentialCreationOptions::class);
    expect($options->challenge)->not()->toBeEmpty();
    expect($options->user)->not()->toBeNull();
    expect($options->rp)->not()->toBeNull();
});

test('getPasskeyCreationOptions stores options in session', function () {
    $this->passkeys->getPasskeyCreationOptions($this->user);

    expect(Session::has($this->passkeys->passkeyCreationOptionsParam))->toBeTrue();
});

test('getPasskeyRequestOptions returns PublicKeyCredentialRequestOptions', function () {
    $options = $this->passkeys->getPasskeyRequestOptions();

    expect($options)->toBeInstanceOf(PublicKeyCredentialRequestOptions::class);
    expect($options->challenge)->not()->toBeEmpty();
    expect($options->userVerification)->toBe(PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED);
});

test('verifyPasskey returns the validated credential source', function () {
    $requestOptionsJson = '{"challenge":"test-challenge"}';
    $responseJson = '{"id":"test-credential-id"}';
    $requestOptions = PublicKeyCredentialRequestOptions::create(challenge: 'test-challenge');
    $authenticatorAssertionResponse = Mockery::mock(AuthenticatorAssertionResponse::class);
    $publicKeyCredential = PublicKeyCredential::create(
        type: 'public-key',
        rawId: 'test-credential-id',
        response: $authenticatorAssertionResponse,
    );
    $credentialRecord = CredentialRecord::create(
        publicKeyCredentialId: 'test-credential-id',
        type: 'public-key',
        transports: [],
        attestationType: 'none',
        trustPath: EmptyTrustPath::create(),
        aaguid: Uuid::v4(),
        credentialPublicKey: 'credential-public-key',
        userHandle: 'user-handle',
        counter: 1,
    );
    $updatedCredentialRecord = clone $credentialRecord;
    $updatedCredentialRecord->counter = 2;

    $serializer = Mockery::mock(SerializerInterface::class);
    $serializer
        ->shouldReceive('deserialize')
        ->once()
        ->with($requestOptionsJson, PublicKeyCredentialRequestOptions::class, 'json')
        ->andReturn($requestOptions);
    $serializer
        ->shouldReceive('deserialize')
        ->once()
        ->with($responseJson, PublicKeyCredential::class, 'json')
        ->andReturn($publicKeyCredential);

    $credentialRepository = Mockery::mock(CredentialRepository::class);
    $credentialRepository
        ->shouldReceive('findOneByCredentialId')
        ->once()
        ->with('test-credential-id')
        ->andReturn($credentialRecord);
    $credentialRepository
        ->shouldReceive('saveCredentialSource')
        ->once()
        ->with($updatedCredentialRecord);

    $assertionResponseValidator = Mockery::mock(AuthenticatorAssertionResponseValidator::class);
    $assertionResponseValidator
        ->shouldReceive('check')
        ->once()
        ->with($credentialRecord, $authenticatorAssertionResponse, $requestOptions, 'localhost', $this->passkeys->passkeyUserEntity($this->user)->id)
        ->andReturn($updatedCredentialRecord);

    $webauthnServer = Mockery::mock(WebauthnServer::class);
    $webauthnServer->shouldReceive('getSerializer')->andReturn($serializer);
    $webauthnServer->shouldReceive('getCredentialRepository')->andReturn($credentialRepository);
    $webauthnServer->shouldReceive('getAuthenticatorAssertionResponseValidator')->andReturn($assertionResponseValidator);

    $property = new ReflectionProperty($this->passkeys, 'webauthnServer');
    $property->setValue($this->passkeys, $webauthnServer);

    expect($this->passkeys->verifyPasskey($this->user, $requestOptionsJson, $responseJson))->toBe($updatedCredentialRecord);
});

test('deletePasskey removes passkey from database', function () {
    WebAuthn::factory()->create([
        'userId' => $this->user->id,
        'credentialId' => 'test-credential-id',
        'uid' => 'test-uid-to-delete',
    ]);

    expect(WebAuthn::where('uid', 'test-uid-to-delete')->exists())->toBeTrue();

    $this->passkeys->deletePasskey($this->user, 'test-uid-to-delete');

    expect(WebAuthn::where('uid', 'test-uid-to-delete')->exists())->toBeFalse();
});

test('deletePasskey handles non-existent passkey gracefully', function () {
    $this->passkeys->deletePasskey($this->user, 'non-existent-uid');

    expect(WebAuthn::count())->toBe(0);
});

test('deletePasskey only deletes specified passkey', function () {
    WebAuthn::factory()->create([
        'userId' => $this->user->id,
        'credentialId' => 'test-credential-id',
        'uid' => 'uid-1',
    ]);

    WebAuthn::factory()->create([
        'userId' => $this->user->id,
        'credentialId' => 'test-credential-id',
        'uid' => 'uid-2',
    ]);

    expect(WebAuthn::count())->toBe(2);

    $this->passkeys->deletePasskey($this->user, 'uid-1');

    expect(WebAuthn::count())->toBe(1);
    expect(WebAuthn::where('uid', 'uid-2')->exists())->toBeTrue();
});
