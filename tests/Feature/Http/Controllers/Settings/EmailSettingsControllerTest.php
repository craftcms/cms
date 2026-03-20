<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Settings\EmailSettingsController;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\SystemMessage\Mailables\SystemMessageMailable;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    actingAs(User::find()->one());
});

it('requires authentication', function () {
    Auth::logout();

    get(action([EmailSettingsController::class, 'index']))
        ->assertRedirect();

    post(action([EmailSettingsController::class, 'store']))
        ->assertRedirect();
});

it('can show the email settings screen', function () {
    get(action([EmailSettingsController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page->component('SettingsEmailPage'))
        ->assertOk();
});

it('shows a readonly settings screen when admin changes is disabled', function () {
    Cms::config()->allowAdminChanges = false;

    get(action([EmailSettingsController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('readOnly', true))
        ->assertOk();
});

it('can save email settings', function () {
    post(action([EmailSettingsController::class, 'store']), [
        'fromEmail' => 'test@example.com',
        'fromName' => 'Test Sender',
        'replyToEmail' => 'reply@example.com',
        'mailer' => null,
        'template' => '_emails/layout',
    ])->assertRedirectBack();

    $config = ProjectConfig::get('email');

    expect($config['fromEmail'])->toBe('test@example.com')
        ->and($config['fromName'])->toBe('Test Sender')
        ->and($config['replyToEmail'])->toBe('reply@example.com')
        ->and($config['template'])->toBe('_emails/layout');
});

it('can save email settings with site overrides', function () {
    $site = Sites::getPrimarySite();

    post(action([EmailSettingsController::class, 'store']), [
        'fromEmail' => 'default@example.com',
        'fromName' => 'Default Sender',
        'siteOverrides' => [
            $site->uid => [
                'fromEmail' => 'site@example.com',
                'fromName' => 'Site Sender',
            ],
        ],
    ])->assertRedirectBack();

    $config = ProjectConfig::get('email');

    expect($config['fromEmail'])->toBe('default@example.com')
        ->and($config['siteOverrides'][$site->uid]['fromEmail'])->toBe('site@example.com')
        ->and($config['siteOverrides'][$site->uid]['fromName'])->toBe('Site Sender');
});

it('can save email settings with template site overrides', function () {
    $site = Sites::getPrimarySite();

    post(action([EmailSettingsController::class, 'store']), [
        'fromEmail' => 'default@example.com',
        'fromName' => 'Default Sender',
        'template' => '_emails/global-layout',
        'siteOverrides' => [
            $site->uid => [
                'fromEmail' => 'site@example.com',
                'template' => '_emails/site-layout',
            ],
        ],
    ])->assertRedirectBack();

    $config = ProjectConfig::get('email');

    expect($config['template'])->toBe('_emails/global-layout')
        ->and($config['siteOverrides'][$site->uid]['fromEmail'])->toBe('site@example.com')
        ->and($config['siteOverrides'][$site->uid]['template'])->toBe('_emails/site-layout');
});

it('validates required fields', function () {
    post(action([EmailSettingsController::class, 'store']), [
        'fromEmail' => '',
        'fromName' => '',
    ])->assertSessionHasErrors(['fromEmail', 'fromName']);
});

it('can send a test email', function () {
    Mail::fake();

    post(action([EmailSettingsController::class, 'test']), [
        'to' => 'test@example.com',
    ])->assertSessionHasNoErrors();

    Mail::assertSent(
        SystemMessageMailable::class,
        fn (SystemMessageMailable $mailable): bool => $mailable->key === 'test_email' &&
            $mailable->hasTo('test@example.com'),
    );
});

it('validates test email recipient', function () {
    post(action([EmailSettingsController::class, 'test']), [
        'to' => 'not-an-email',
    ])->assertSessionHasErrors('to');
});
