<?php

declare(strict_types=1);

use CraftCms\Cms\User\Models\User;

test('refuses to create a user with a taken username and email', function (string $password) {
    $options = [
        '--email' => 'jane@example.test',
        '--username' => 'jane',
        '--password' => $password,
        '--admin' => '0',
        '--activate' => '1',
        '--no-interaction' => true,
    ];

    $this->artisan('craft:users:create', $options)->assertSuccessful();

    $this->artisan('craft:users:create', $options)
        ->expectsOutputToContain('has already been taken.')
        ->assertFailed();

    expect(User::where('username', 'jane')->count())->toBe(1)
        ->and(User::where('email', 'jane@example.test')->count())->toBe(1);
})->with([
    'with a password' => 'Secret-12345',
    'with an empty password' => '',
]);
