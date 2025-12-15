<?php

use CraftCms\Cms\Http\Controllers\Users\PasskeysController;
use CraftCms\Cms\User\Elements\User;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(User::findOne());
});

it('requires login', function () {
    auth()->logout();

    get(action([PasskeysController::class, 'index']))->assertRedirect('admin/login');
});

test('index', function () {
    get(action([PasskeysController::class, 'index']))
        ->assertOk()
        ->assertSee(t('Passkeys'));
});
