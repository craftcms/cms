<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Middleware\RequireConfirmedPassword;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    Route::post('_test/confirmed-password', fn () => response()->noContent())
        ->middleware(RequireConfirmedPassword::class);

    actingAs(User::findOne());
});

test('password confirmation middleware returns 423', function () {
    postJson('_test/confirmed-password')->assertStatus(423);
});

test('password confirmation middleware uses the configured timeout', function () {
    config()->set('auth.password_timeout', 10);
    Session::passwordConfirmed();

    postJson('_test/confirmed-password')->assertNoContent();

    $this->travel(11)->seconds();

    postJson('_test/confirmed-password')->assertStatus(423);
});

test('password confirmation middleware can be disabled', function () {
    config()->set('auth.password_timeout', -1);

    postJson('_test/confirmed-password')->assertNoContent();
});
