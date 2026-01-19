<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Controllers\IconController;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

test('svg returns JSON response with icon SVG markup', function () {
    get(action([IconController::class, 'svg'], ['icon' => 'gear']))
        ->assertOk()
        ->assertHeader('content-type', 'application/json')
        ->assertJsonStructure(['iconSvg']);
});

test('svg requires icon parameter', function () {
    postJson(action([IconController::class, 'svg']))
        ->assertJsonValidationErrors(['icon']);
});

test('svg validates icon is string', function () {
    postJson(action([IconController::class, 'svg']), [
        'icon' => 123,
    ])->assertJsonValidationErrors(['icon']);
});

test('svg processes system icon names', function () {
    $json = get(action([IconController::class, 'svg'], ['icon' => 'gear']))
        ->assertOk()
        ->json();

    expect($json['iconSvg'])
        ->toBeString()
        ->toContain('<svg');
});

test('svg processes legacy icon names', function () {
    $json = get(action([IconController::class, 'svg'], ['icon' => 'settings']))
        ->assertOk()
        ->json();

    expect($json['iconSvg'])
        ->toBeString()
        ->toContain('<svg');
});

test('svg handles custom icons', function () {
    $json = get(action([IconController::class, 'svg'], ['icon' => 'whiskey-glass-ice']))
        ->assertOk()
        ->json();

    expect($json['iconSvg'])
        ->toBeString()
        ->toContain('<svg');
});

test('svg returns empty string for invalid icons', function () {
    $json = get(action([IconController::class, 'svg'], ['icon' => 'non-existent-icon-xyz']))
        ->assertOk()
        ->json();

    expect($json['iconSvg'])->toBeString();
});
