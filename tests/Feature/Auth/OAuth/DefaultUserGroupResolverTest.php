<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\OAuth\Actions\UserGroupResolver;
use CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition;
use CraftCms\Cms\Tests\TestClasses\OAuth\FakeOAuthProvider;
use CraftCms\Cms\User\Elements\User;

test('it returns the configured provider group ids', function () {
    $resolver = new UserGroupResolver;

    $groupIds = $resolver->handle(
        new ProviderDefinition(
            handle: 'test',
            driver: 'test',
            providerClass: null,
            name: 'Test',
            label: 'Continue with Test',
            clientId: null,
            clientSecret: null,
            groupIds: [1, 2, 3],
        ),
        FakeOAuthProvider::fakeUser([
            'id' => 'provider-user-groups',
        ]),
        User::findOne(),
        'provider-user-groups',
    );

    expect($groupIds)->toBe([1, 2, 3]);
});
