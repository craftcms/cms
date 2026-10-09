<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\OAuth\Actions\UserPopulator;
use CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Tests\TestClasses\OAuth\FakeOAuthProvider;
use CraftCms\Cms\User\Elements\User;

function defaultUserPopulatorProviderDefinition(): ProviderDefinition
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
    app(GeneralConfig::class)->useEmailAsUsername = false;
});

test('it fills the email and full name', function () {
    $user = User::findOne();
    $user->email = null;
    $user->fullName = null;
    $user->username = '';

    $populator = app(UserPopulator::class);

    $populatedUser = $populator->handle(
        defaultUserPopulatorProviderDefinition(),
        FakeOAuthProvider::fakeUser([
            'id' => 'provider-user-populate',
            'email' => 'populated@example.com',
            'name' => 'Populated User',
            'nickname' => 'populated-nickname',
        ]),
        $user,
        'provider-user-populate',
        false,
    );

    expect($populatedUser->email)->toBe('populated@example.com')
        ->and($populatedUser->fullName)->toBe('Populated User')
        ->and($populatedUser->username)->toBe('populated-nickname');
});

test('it resolves usernames from the configured fallbacks', function (
    bool $useEmailAsUsername,
    array $attributes,
    string $expectedUsername,
) {
    app(GeneralConfig::class)->useEmailAsUsername = $useEmailAsUsername;

    $user = User::findOne();
    $user->email = null;
    $user->username = '';

    $populator = app(UserPopulator::class);

    $populatedUser = $populator->handle(
        defaultUserPopulatorProviderDefinition(),
        FakeOAuthProvider::fakeUser(array_merge([
            'id' => 'provider-user-username',
            'name' => null,
        ], $attributes)),
        $user,
        'provider-user-username',
        false,
    );

    expect($populatedUser->username)->toBe($expectedUsername);
})->with([
    'email as username' => [true, [
        'email' => 'email-username@example.com',
        'nickname' => 'nickname-ignored',
    ], 'email-username@example.com'],
    'nickname fallback' => [false, [
        'email' => 'nickname@example.com',
        'nickname' => 'nickname-preferred',
    ], 'nickname-preferred'],
    'email fallback' => [false, [
        'email' => 'email-fallback@example.com',
        'nickname' => null,
    ], 'email-fallback@example.com'],
    'provider identity fallback' => [false, [
        'email' => null,
        'nickname' => null,
    ], 'test_provider-user-username'],
]);
