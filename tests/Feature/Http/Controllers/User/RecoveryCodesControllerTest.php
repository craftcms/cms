<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\Auth;
use CraftCms\Cms\Auth\Methods\RecoveryCodes;
use CraftCms\Cms\Http\Controllers\Users\RecoveryCodesController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Session;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
    Session::passwordConfirmed();
});

describe('generate', function () {
    it('requires login', function () {
        auth()->logout();

        postJson(action([RecoveryCodesController::class, 'generate']))
            ->assertUnauthorized();
    });

    it('requires password confirmation', function () {
        Session::forget('auth.password_confirmed_at');

        postJson(action([RecoveryCodesController::class, 'generate']))
            ->assertForbidden();
    });

    it('generates recovery codes successfully', function () {
        postJson(action([RecoveryCodesController::class, 'generate']))
            ->assertOk()
            ->assertJsonStructure(['message', 'codes']);
    });

    it('returns success message with codes array', function () {
        postJson(action([RecoveryCodesController::class, 'generate']))
            ->assertOk()
            ->assertJson(['message' => 'Recovery codes generated.'])
            ->assertJsonStructure(['codes']);
    });

    it('generates 10 recovery codes', function () {
        $response = postJson(action([RecoveryCodesController::class, 'generate']))
            ->assertOk()
            ->json();

        expect($response['codes'])->toBeArray();
        expect($response['codes'])->toHaveCount(10);
    });

    it('generates unique codes', function () {
        $response = postJson(action([RecoveryCodesController::class, 'generate']))
            ->assertOk()
            ->json();

        $codes = $response['codes'];
        $uniqueCodes = array_unique($codes);

        expect(count($codes))->toBe(count($uniqueCodes));
    });
});

describe('download', function () {
    it('requires login', function () {
        auth()->logout();

        postJson(action([RecoveryCodesController::class, 'download']))
            ->assertUnauthorized();
    });

    it('requires password confirmation', function () {
        Session::forget('auth.password_confirmed_at');

        postJson(action([RecoveryCodesController::class, 'download']))
            ->assertForbidden();
    });

    it('returns 400 when no recovery codes exist', function () {
        postJson(action([RecoveryCodesController::class, 'download']))
            ->assertStatus(400);
    });

    it('downloads recovery codes as text file', function () {
        // First generate codes
        $auth = app(Auth::class);
        $recoveryCodes = $auth->getMethod(RecoveryCodes::class);
        $recoveryCodes->generateRecoveryCodes();

        $response = postJson(action([RecoveryCodesController::class, 'download']))
            ->assertOk();

        expect($response->headers->get('Content-Type'))->toStartWith('text/plain');
        expect($response->headers->get('Content-Disposition'))->toContain('attachment');
    });

    it('file contains correct headers', function () {
        $auth = app(Auth::class);
        $recoveryCodes = $auth->getMethod(RecoveryCodes::class);
        $recoveryCodes->generateRecoveryCodes();

        $response = postJson(action([RecoveryCodesController::class, 'download']))
            ->assertOk();

        expect($response->headers->get('Content-Type'))->toStartWith('text/plain');
        expect($response->headers->get('Content-Disposition'))->toContain('attachment');
        expect($response->headers->get('Content-Disposition'))->toContain('.txt');
    });

    it('file contains system name', function () {
        $auth = app(Auth::class);
        $recoveryCodes = $auth->getMethod(RecoveryCodes::class);
        $recoveryCodes->generateRecoveryCodes();

        $content = postJson(action([RecoveryCodesController::class, 'download']))
            ->assertOk()
            ->getContent();

        expect($content)->toContain('Recovery Codes for');
    });

    it('file contains user account info', function () {
        $auth = app(Auth::class);
        $recoveryCodes = $auth->getMethod(RecoveryCodes::class);
        $recoveryCodes->generateRecoveryCodes();

        $user = auth()->user();
        $content = postJson(action([RecoveryCodesController::class, 'download']))
            ->assertOk()
            ->getContent();

        expect($content)->toContain('Account:');
        expect($content)->toContain($user->email);
    });

    it('file contains all recovery codes', function () {
        $auth = app(Auth::class);
        $recoveryCodes = $auth->getMethod(RecoveryCodes::class);
        $codes = $recoveryCodes->generateRecoveryCodes();

        $content = postJson(action([RecoveryCodesController::class, 'download']))
            ->assertOk()
            ->getContent();

        foreach ($codes as $code) {
            if ($code) {
                expect($content)->toContain($code);
            }
        }
    });

    it('file contains generation date', function () {
        $auth = app(Auth::class);
        $recoveryCodes = $auth->getMethod(RecoveryCodes::class);
        $recoveryCodes->generateRecoveryCodes();

        $content = postJson(action([RecoveryCodesController::class, 'download']))
            ->assertOk()
            ->getContent();

        expect($content)->toContain('Generated:');
    });

    it('file contains website information', function () {
        $auth = app(Auth::class);
        $recoveryCodes = $auth->getMethod(RecoveryCodes::class);
        $recoveryCodes->generateRecoveryCodes();

        $content = postJson(action([RecoveryCodesController::class, 'download']))
            ->assertOk()
            ->getContent();

        expect($content)->toContain('Website:');
    });

    it('file contains usage instructions', function () {
        $auth = app(Auth::class);
        $recoveryCodes = $auth->getMethod(RecoveryCodes::class);
        $recoveryCodes->generateRecoveryCodes();

        $content = postJson(action([RecoveryCodesController::class, 'download']))
            ->assertOk()
            ->getContent();

        expect($content)->toContain('backup form of verification');
        expect($content)->toContain('Each code can only be used once');
    });
});
