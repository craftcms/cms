<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Auth\LoginController;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;

describe('renderViewWithFallback', function () {
    test('returns Inertia response with correct component for CP requests', function () {
        get(action([LoginController::class, 'showLogin']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('auth/Login'));
    });

    test('Inertia response includes expected props', function () {
        get(action([LoginController::class, 'showLogin']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('action')
                ->has('username')
            );
    });

    test('username prop is empty when rememberUsernameDuration is disabled', function () {
        Cms::config()->rememberUsernameDuration = 0;

        get(action([LoginController::class, 'showLogin']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('username', '')
            );
    });
});
