<?php

declare(strict_types=1);

use CraftCms\Cms\User\Elements\User;
use CraftCms\Yii2Adapter\Http\LoginModalController;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;

uses(DatabaseTestCase::class);

it('requires an email', function(): void {
    $this->postJson(action(LoginModalController::class), [])
        ->assertJsonValidationErrors(['email']);
});

it('returns the login modal', function(): void {
    $this->postJson(action(LoginModalController::class), [
        'email' => 'test@example.com',
    ])
        ->assertOk()
        ->assertJsonStructure(['html', 'headHtml', 'bodyHtml'])
        ->assertJsonPath('html', fn(string $html): bool => str_contains($html, 'craft-login-form'));
});

it('requires an email for an elevated session when not impersonating', function(): void {
    $this->actingAs(User::findOne());

    $this->postJson(action(LoginModalController::class), [
        'forElevatedSession' => true,
    ])
        ->assertJsonValidationErrors(['email']);
});
