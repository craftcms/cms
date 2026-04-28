<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\OAuth\Actions\UserResolver;
use CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition;
use CraftCms\Cms\Auth\OAuth\Events\ResolvingOAuthUserLink;
use CraftCms\Cms\Auth\OAuth\OAuth;
use CraftCms\Cms\Tests\TestClasses\OAuth\FakeOAuthProvider;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Event;

function defaultUserResolverProviderDefinition(): ProviderDefinition
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

beforeEach(function () {
    Event::forget(ResolvingOAuthUserLink::class);
});

test('it returns an explicitly linked user', function () {
    $user = UserModel::factory()->active()->createElement([
        'email' => 'linked-user@example.com',
        'username' => 'linked-user',
    ]);

    $provider = defaultUserResolverProviderDefinition();
    app(OAuth::class)->linkIdentity($user, $provider, 'linked-identity');

    $resolver = app(UserResolver::class);

    $resolvedUser = $resolver->handle($provider, FakeOAuthProvider::fakeUser([
        'id' => 'different-provider-id',
        'email' => 'other@example.com',
    ]), 'linked-identity');

    expect($resolvedUser?->id)->toBe($user->id);
});

test('it allows the user-link event to provide a user', function () {
    $user = UserModel::factory()->active()->createElement([
        'email' => 'event-user@example.com',
        'username' => 'event-user',
    ]);

    Event::listen(ResolvingOAuthUserLink::class, function (ResolvingOAuthUserLink $event) use ($user) {
        $event->user = $user;
    });

    $resolver = app(UserResolver::class);

    $resolvedUser = $resolver->handle(
        defaultUserResolverProviderDefinition(),
        FakeOAuthProvider::fakeUser([
            'id' => 'provider-user-event',
            'email' => null,
        ]),
        'provider-user-event',
    );

    expect($resolvedUser?->id)->toBe($user->id);
});

test('it falls back to matching by email', function () {
    $user = UserModel::factory()->active()->createElement([
        'email' => 'fallback-user@example.com',
        'username' => 'fallback-user',
    ]);

    $resolver = app(UserResolver::class);

    $resolvedUser = $resolver->handle(
        defaultUserResolverProviderDefinition(),
        FakeOAuthProvider::fakeUser([
            'id' => 'provider-user-email-fallback',
            'email' => 'fallback-user@example.com',
        ]),
        'provider-user-email-fallback',
    );

    expect($resolvedUser?->id)->toBe($user->id);
});

test('it returns null when no linked user or email is available', function () {
    $resolver = app(UserResolver::class);

    $resolvedUser = $resolver->handle(
        defaultUserResolverProviderDefinition(),
        FakeOAuthProvider::fakeUser([
            'id' => 'provider-user-no-match',
            'email' => null,
        ]),
        'provider-user-no-match',
    );

    expect($resolvedUser)->toBeNull();
});
