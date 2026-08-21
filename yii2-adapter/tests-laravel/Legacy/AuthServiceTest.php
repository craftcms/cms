<?php

declare(strict_types=1);

use craft\services\Auth;
use CraftCms\Cms\Auth\Passkeys\CredentialRepository;
use CraftCms\Cms\Auth\Passkeys\Passkeys;
use CraftCms\Cms\Auth\Passkeys\WebauthnServer;
use Symfony\Component\Uid\Uuid;
use Webauthn\CredentialRecord;
use Webauthn\TrustPath\EmptyTrustPath;

it('persists the credential returned by passkey verification', function() {
    $user = Mockery::mock(craft\elements\User::class);
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

    $credentialRepository = Mockery::mock(CredentialRepository::class);
    $credentialRepository->shouldReceive('saveCredentialSource')->once()->with($credentialRecord);

    $webauthnServer = Mockery::mock(WebauthnServer::class);
    $webauthnServer->shouldReceive('getCredentialRepository')->once()->andReturn($credentialRepository);

    $passkeys = Mockery::mock(Passkeys::class)->makePartial();
    $passkeys->shouldReceive('verifyPasskey')->once()->andReturn($credentialRecord);
    $passkeys->shouldReceive('webauthnServer')->once()->andReturn($webauthnServer);
    app()->instance(Passkeys::class, $passkeys);

    expect(new Auth()->verifyPasskey($user, '{}', '{}'))->toBeTrue();
});
