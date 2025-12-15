<?php

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Users\PasswordController;
use CraftCms\Cms\User\Elements\User;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(User::findOne());
});

it('requires login', function () {
    auth()->logout();

    get(action([PasswordController::class, 'index']))->assertRedirect(Cms::config()->cpTrigger.'/login');
});

test('index', function () {
    get(action([PasswordController::class, 'index']))
        ->assertOk()
        ->assertSee(t('Password'));
});
