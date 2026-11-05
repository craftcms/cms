<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Assets\IconController;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(User::findOne());
    $this->cpTrigger = Cms::config()->cpTrigger;
});

it('requires authentication', function () {
    auth()->logout();

    get(action(IconController::class, ['extension' => 'jpg']))
        ->assertRedirect();
});

it('returns SVG for known extension', function () {
    get(action(IconController::class, ['extension' => 'jpg']))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml');
});

it('returns SVG for unknown extension', function () {
    get(action(IconController::class, ['extension' => 'xyz']))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml');
});

it('returns the dark icon for the dark theme', function () {
    get(action(IconController::class, ['extension' => 'pdf', 'theme' => 'dark']))
        ->assertOk()
        ->assertSee('fill="#1a2231"', false)
        ->assertSee('fill="#a6b2c2"', false);

    get(action(IconController::class, ['extension' => 'pdf']))
        ->assertOk()
        ->assertDontSee('fill="#1a2231"', false)
        ->assertSee('fill="#5c6982"', false);
});
