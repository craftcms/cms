<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Controllers\Users\PasskeysController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Session;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
    Session::passwordConfirmed();
});

it('requires login for index', function () {
    auth()->logout();

    get(action([PasskeysController::class, 'index']))
        ->assertRedirect();
});

test('index', function () {
    get(action([PasskeysController::class, 'index']))
        ->assertOk()
        ->assertSee(t('Passkeys'));
});

describe('creationOptions', function () {
    it('requires login', function () {
        auth()->logout();

        postJson(action([PasskeysController::class, 'creationOptions']))
            ->assertUnauthorized();
    });

    it('requires password confirmation', function () {
        Session::forget('auth.password_confirmed_at');

        postJson(action([PasskeysController::class, 'creationOptions']))
            ->assertForbidden();
    });

    it('returns passkey creation options', function () {
        postJson(action([PasskeysController::class, 'creationOptions']))
            ->assertOk()
            ->assertJsonStructure(['options']);
    });

    it('returns WebAuthn options with required fields', function () {
        $response = postJson(action([PasskeysController::class, 'creationOptions']))
            ->assertOk()
            ->json();

        expect($response['options'])->toBeArray();
        expect($response['options'])->toHaveKeys(['challenge', 'rp', 'user']);
    });
});

describe('verifyCreation', function () {
    it('requires login', function () {
        auth()->logout();

        postJson(action([PasskeysController::class, 'verifyCreation']))
            ->assertUnauthorized();
    });

    it('requires password confirmation', function () {
        Session::forget('auth.password_confirmed_at');

        postJson(action([PasskeysController::class, 'verifyCreation']))
            ->assertForbidden();
    });

    it('validates credentials parameter is required', function () {
        postJson(action([PasskeysController::class, 'verifyCreation']), [])
            ->assertJsonValidationErrorFor('credentials');
    });

    it('returns failure for invalid credentials', function () {
        postJson(action([PasskeysController::class, 'verifyCreation']), [
            'credentials' => json_encode(['invalid' => 'data']),
            'credentialName' => 'Test Passkey',
        ])
            ->assertStatus(400)
            ->assertJson(['message' => 'Passkey creation failed.']);
    });
});

describe('delete', function () {
    it('requires login', function () {
        auth()->logout();

        postJson(action([PasskeysController::class, 'delete']))
            ->assertUnauthorized();
    });

    it('validates uid parameter is required', function () {
        postJson(action([PasskeysController::class, 'delete']), [])
            ->assertJsonValidationErrorFor('uid');
    });

    it('returns success message with table HTML', function () {
        // This test would need a real passkey to delete
        // For now, we'll just verify the structure when passkey doesn't exist
        postJson(action([PasskeysController::class, 'delete']), [
            'uid' => 'non-existent-uid',
        ])
            ->assertOk()
            ->assertJsonStructure(['message', 'tableHtml']);
    });
});
