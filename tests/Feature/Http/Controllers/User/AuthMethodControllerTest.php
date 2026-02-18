<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\Auth;
use CraftCms\Cms\Auth\Methods\RecoveryCodes;
use CraftCms\Cms\Http\Controllers\Users\AuthMethodController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Session;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
    Session::passwordConfirmed();
});

it('requires login', function () {
    auth()->logout();

    postJson(action([AuthMethodController::class, 'setupHtml']), ['method' => 'SomeMethod'])
        ->assertUnauthorized();

    postJson(action([AuthMethodController::class, 'listingHtml']))
        ->assertUnauthorized();

    postJson(action([AuthMethodController::class, 'destroy']), ['method' => 'SomeMethod'])
        ->assertUnauthorized();
});

describe('setupHtml', function () {
    it('validates method parameter is required', function () {
        postJson(action([AuthMethodController::class, 'setupHtml']), [])
            ->assertJsonValidationErrorFor('method');
    });

    it('returns setup HTML for valid method', function () {
        postJson(action([AuthMethodController::class, 'setupHtml']), [
            'method' => RecoveryCodes::class,
        ])
            ->assertOk()
            ->assertJsonStructure([
                'containerId',
                'html',
                'headHtml',
                'bodyHtml',
                'methodName',
            ]);
    });

    it('generates unique container ID', function () {
        $response1 = postJson(action([AuthMethodController::class, 'setupHtml']), [
            'method' => RecoveryCodes::class,
        ])->json();

        $response2 = postJson(action([AuthMethodController::class, 'setupHtml']), [
            'method' => RecoveryCodes::class,
        ])->json();

        expect($response1['containerId'])->not->toBe($response2['containerId']);
    });

    it('returns method display name', function () {
        postJson(action([AuthMethodController::class, 'setupHtml']), [
            'method' => RecoveryCodes::class,
        ])
            ->assertOk()
            ->assertJsonPath('methodName', 'Recovery Codes');
    });
});

describe('listingHtml', function () {
    it('returns auth methods listing HTML', function () {
        postJson(action([AuthMethodController::class, 'listingHtml']))
            ->assertOk()
            ->assertJsonStructure([
                'html',
                'headHtml',
                'bodyHtml',
            ]);
    });

    it('returns HTML content', function () {
        $response = postJson(action([AuthMethodController::class, 'listingHtml']))
            ->assertOk()
            ->json();

        expect($response['html'])->toBeString();
    });
});

describe('destroy', function () {
    it('requires password confirmation', function () {
        Session::forget('auth.password_confirmed_at');

        postJson(action([AuthMethodController::class, 'destroy']), [
            'method' => RecoveryCodes::class,
        ])->assertForbidden();
    });

    it('validates method parameter is required', function () {
        postJson(action([AuthMethodController::class, 'destroy']), [])
            ->assertJsonValidationErrorFor('method');
    });

    it('removes authentication method successfully', function () {
        // First, add the method
        $auth = app(Auth::class);
        $recoveryCodes = $auth->getMethod(RecoveryCodes::class);
        $recoveryCodes->generateRecoveryCodes();

        expect($recoveryCodes->isActive())->toBeTrue();

        // Now remove it
        postJson(action([AuthMethodController::class, 'destroy']), [
            'method' => RecoveryCodes::class,
        ])
            ->assertOk()
            ->assertJson(['message' => 'Authentication method removed.']);

        // Verify it's removed
        $recoveryCodes = $auth->getMethod(RecoveryCodes::class);
        expect($recoveryCodes->isActive())->toBeFalse();
    });

    it('returns success message', function () {
        $auth = app(Auth::class);
        $recoveryCodes = $auth->getMethod(RecoveryCodes::class);
        $recoveryCodes->generateRecoveryCodes();

        postJson(action([AuthMethodController::class, 'destroy']), [
            'method' => RecoveryCodes::class,
        ])
            ->assertOk()
            ->assertJsonFragment(['message' => 'Authentication method removed.']);
    });
});
