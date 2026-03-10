<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Utilities\MailSettingsController;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\Utility\Utilities\MailSettings;
use Illuminate\Support\Facades\Gate;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

beforeEach(function () {
    actingAs(User::findOne());
});

test('unauthorized users cannot access mail settings utility actions', function () {
    Cms::config()->disabledUtilities = [MailSettings::id()];

    post(action(MailSettingsController::class), [
        'to' => 'test@example.com',
    ])->assertForbidden();
});

test('non-admin users cannot access mail settings utility actions even if granted the utility ability', function () {
    $user = UserModel::factory()->createElement([
        'admin' => false,
    ]);
    $user = User::find()->id($user->id)->one();

    Gate::define(sprintf('utility:%s', MailSettings::id()), fn () => true);

    actingAs($user);

    post(action(MailSettingsController::class), [
        'to' => 'test@example.com',
    ])->assertForbidden();
});

test('requires a valid email address', function () {
    post(action(MailSettingsController::class), [
        'to' => 'not-an-email',
    ])->assertSessionHasErrors('to');
});

test('can send a test email', function () {
    post(action(MailSettingsController::class), [
        'to' => 'test@example.com',
    ])
        ->assertRedirectBack()
        ->assertSessionHas('success', t('Email sent successfully! Check your inbox.'));
});
