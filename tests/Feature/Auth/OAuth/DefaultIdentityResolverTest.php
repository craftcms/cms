<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\OAuth\Actions\IdentityResolver;
use CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition;
use CraftCms\Cms\Tests\TestClasses\OAuth\FakeOAuthProvider;

function defaultIdentityResolverProviderDefinition(): ProviderDefinition
{
    return new ProviderDefinition(
        handle: 'test',
        driver: 'test',
        providerClass: null,
        name: 'Test',
        label: 'Continue with Test',
        clientId: null,
        clientSecret: null,
    );
}

test('it trims scalar identities', function () {
    $resolver = new IdentityResolver;

    $identity = $resolver->handle(
        defaultIdentityResolverProviderDefinition(),
        FakeOAuthProvider::fakeUser(['id' => '  provider-user  ']),
    );

    expect($identity)->toBe('provider-user');
});

test('it rejects missing identities', function () {
    $resolver = new IdentityResolver;

    expect(fn () => $resolver->handle(
        defaultIdentityResolverProviderDefinition(),
        FakeOAuthProvider::fakeUser(['id' => '   ']),
    ))->toThrow(RuntimeException::class, 'OAuth provider [test] did not return an identity.');
});
