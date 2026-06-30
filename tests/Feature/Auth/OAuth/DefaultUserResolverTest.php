<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\OAuth\Actions\UserResolver;
use CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition;
use CraftCms\Cms\Auth\OAuth\Events\OAuthUserLinkResolving;
use CraftCms\Cms\Auth\OAuth\OAuth;
use CraftCms\Cms\Tests\TestClasses\OAuth\FakeOAuthProvider;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Event;

function defaultUserResolverProviderDefinition(bool $trustsEmail = false): ProviderDefinition
{
    return new ProviderDefinition(
        handle: 'test',
        driver: 'test',
        providerClass: null,
        name: 'Test',
        label: 'Continue with Test',
        clientId: null,
        clientSecret: null,
        trustsEmail: $trustsEmail,
    );
}

beforeEach(function () {
    Event::forget(OAuthUserLinkResolving::class);
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

    Event::listen(OAuthUserLinkResolving::class, function (OAuthUserLinkResolving $event) use ($user) {
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

test('it falls back to matching by email for trusted providers', function () {
    $user = UserModel::factory()->active()->createElement([
        'email' => 'fallback-user@example.com',
        'username' => 'fallback-user',
    ]);

    $resolver = app(UserResolver::class);

    $resolvedUser = $resolver->handle(
        defaultUserResolverProviderDefinition(trustsEmail: true),
        FakeOAuthProvider::fakeUser([
            'id' => 'provider-user-email-fallback',
            'email' => 'fallback-user@example.com',
        ]),
        'provider-user-email-fallback',
    );

    expect($resolvedUser?->id)->toBe($user->id);
});

test('it does not fall back to matching by email for untrusted providers', function () {
    UserModel::factory()->active()->createElement([
        'email' => 'untrusted-fallback-user@example.com',
        'username' => 'untrusted-fallback-user',
    ]);

    $resolver = app(UserResolver::class);

    $resolvedUser = $resolver->handle(
        defaultUserResolverProviderDefinition(),
        FakeOAuthProvider::fakeUser([
            'id' => 'provider-user-untrusted-email',
            'email' => 'untrusted-fallback-user@example.com',
        ]),
        'provider-user-untrusted-email',
    );

    expect($resolvedUser)->toBeNull();
});

test('it only matches trusted provider emails against user emails', function () {
    UserModel::factory()->active()->createElement([
        'email' => 'actual-email@example.com',
        'username' => 'username-only-match',
    ]);

    $resolver = app(UserResolver::class);

    $resolvedUser = $resolver->handle(
        defaultUserResolverProviderDefinition(trustsEmail: true),
        FakeOAuthProvider::fakeUser([
            'id' => 'provider-user-username-value',
            'email' => 'username-only-match',
        ]),
        'provider-user-username-value',
    );

    expect($resolvedUser)->toBeNull();
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
