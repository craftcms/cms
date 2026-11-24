<?php

declare(strict_types=1);

use CraftCms\Cms\User\Models\User;

test('refuses to create a user with a taken username or email', function (array $options, string $error) {
    $this->artisan('craft:users:create', [
        '--email' => 'jane@example.test',
        '--username' => 'jane',
        '--password' => 'Secret-12345',
        '--admin' => '0',
        '--activate' => '1',
        '--no-interaction' => true,
    ])->assertSuccessful();

    $userCount = User::count();

    $this->artisan('craft:users:create', [
        ...$options,
        '--admin' => '0',
        '--activate' => '1',
        '--no-interaction' => true,
    ])
        ->expectsOutputToContain($error)
        ->assertFailed();

    expect(User::count())->toBe($userCount);
})->with([
    'taken email' => [
        ['--email' => 'jane@example.test', '--username' => 'john', '--password' => 'Secret-12345'],
        'The email has already been taken.',
    ],
    'taken username' => [
        ['--email' => 'john@example.test', '--username' => 'jane', '--password' => 'Secret-12345'],
        'The username has already been taken.',
    ],
    'taken email and username without a password' => [
        ['--email' => 'jane@example.test', '--username' => 'jane', '--password' => ''],
        'has already been taken.',
    ],
]);
