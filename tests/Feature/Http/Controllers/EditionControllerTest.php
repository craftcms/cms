<?php

declare(strict_types=1);

use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\EditionController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

test('requires authentication', function () {
    Auth::logout();

    postJson(action([EditionController::class, 'tryEdition']), [
        'edition' => 'pro',
    ])->assertUnauthorized();

    postJson(action([EditionController::class, 'switchToLicensedEdition']))
        ->assertUnauthorized();
});

test('tryEdition validates required edition', function () {
    postJson(action([EditionController::class, 'tryEdition']), [])
        ->assertJsonValidationErrors(['edition']);
});

test('tryEdition rejects invalid edition', function () {
    postJson(action([EditionController::class, 'tryEdition']), [
        'edition' => 'invalid-edition',
    ])->assertBadRequest();
});

test('tryEdition sets the edition', function () {
    $originalEdition = Edition::get();

    postJson(action([EditionController::class, 'tryEdition']), [
        'edition' => 'pro',
    ])->assertOk();

    expect(Edition::get()->handle())->toBe('pro');

    Edition::set($originalEdition);
});

test('tryEdition allows downgrading edition', function () {
    Edition::set(Edition::Pro);

    postJson(action([EditionController::class, 'tryEdition']), [
        'edition' => 'solo',
    ])->assertOk();

    expect(Edition::get()->handle())->toBe('solo');

    Edition::set(Edition::Solo);
});

test('switchToLicensedEdition returns success even when edition is correct', function () {
    Edition::set(Edition::Solo);

    postJson(action([EditionController::class, 'switchToLicensedEdition']))
        ->assertOk();

    expect(Edition::get())->toBe(Edition::Solo);
});

test('switchToLicensedEdition switches to licensed edition when wrong', function () {
    Edition::set(Edition::Pro);

    postJson(action([EditionController::class, 'switchToLicensedEdition']))
        ->assertOk();

    expect(Edition::get()->value)->toBeLessThanOrEqual(Edition::Pro->value);
});
