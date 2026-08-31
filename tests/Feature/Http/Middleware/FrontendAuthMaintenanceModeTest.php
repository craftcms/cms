<?php

declare(strict_types=1);

use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeAll(fn () => putenv('CRAFT_LOGIN_PATH=sign-in'));

afterAll(fn () => putenv('CRAFT_LOGIN_PATH'));

test('localized frontend login is accessible during maintenance mode', function () {
    $user = User::factory()
        ->withPermissions(['accessSiteWhenSystemIsOff'])
        ->createElement();
    auth()->logout();
    app()->maintenanceMode()->activate([]);

    get('/sign-in')->assertOk();

    post('/sign-in', [
        'loginName' => $user->username,
        'password' => 'password',
    ])->assertRedirect();

    expect(Auth::id())->toBe($user->id);
});
