<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Settings\EmailSettingsController;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\SystemMessage\Mailables\SystemMessageMailable;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    actingAs(User::find()->one());
});

afterEach(function () {
    putenv('EMAIL_SETTINGS_FROM_EMAIL');
    putenv('EMAIL_SETTINGS_FROM_NAME');
    putenv('EMAIL_SETTINGS_MAILER');
    putenv('EMAIL_SETTINGS_MISSING');
    putenv('EMAIL_SETTINGS_SITE_EMAIL');
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
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/Email')
            ->has('form.nodes')
            ->has('form.values.fromEmail')
            ->has('form.values.fromName')
            ->where('submit.method', 'post')
            ->where('submit.url', action([EmailSettingsController::class, 'store'])))
        ->assertOk();
});

it('shows a readonly settings screen when admin changes is disabled', function () {
    Cms::config()->allowAdminChanges = false;

    get(action([EmailSettingsController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('form.nodes', function (Collection $nodes): bool {
                $controls = $nodes->pluck('control')->filter();

                return $controls->isNotEmpty()
                    && $controls->every(fn (array $control): bool => $control['mode'] === 'readOnly');
            }))
        ->assertOk();
});

it('includes configured overrides for every site in the form', function () {
    $site = Site::factory()->create(['name' => 'French']);
    Sites::refreshSites();
    ProjectConfig::set('email', [
        'siteOverrides' => [
            $site->uid => ['fromEmail' => 'french@example.com'],
        ],
    ], 'Set test email settings.');

    get(action([EmailSettingsController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('form.nodes', fn (Collection $nodes): bool => $nodes
                ->pluck('control')
                ->filter()
                ->contains(fn (array $control): bool => $control['component'] === 'craft:table'
                    && $control['path'] === ['siteOverrides']))
            ->where("form.values.siteOverrides.{$site->uid}.site", 'French')
            ->where("form.values.siteOverrides.{$site->uid}.fromEmail", 'french@example.com')
            ->where("form.values.siteOverrides.{$site->uid}.fromName", '')
            ->where("form.values.siteOverrides.{$site->uid}.replyToEmail", '')
            ->where("form.values.siteOverrides.{$site->uid}.template", ''))
        ->assertOk();
});

it('escapes site names rendered as table headings', function () {
    $site = Site::factory()->create(['name' => '<img src=x onerror=alert(1)>']);
    Sites::refreshSites();

    get(action([EmailSettingsController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where("form.values.siteOverrides.{$site->uid}.site", '&lt;img src=x onerror=alert(1)&gt;'));
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

it('can save email settings with an environment variable from email', function () {
    putenv('EMAIL_SETTINGS_FROM_EMAIL=test@example.com');

    post(action([EmailSettingsController::class, 'store']), [
        'fromEmail' => '$EMAIL_SETTINGS_FROM_EMAIL',
        'fromName' => 'Test Sender',
    ])->assertRedirectBack()
        ->assertSessionHasNoErrors();

    expect(ProjectConfig::get('email')['fromEmail'])->toBe('$EMAIL_SETTINGS_FROM_EMAIL');
});

it('validates resolved environment variable from email values', function () {
    putenv('EMAIL_SETTINGS_FROM_EMAIL=not-an-email');

    post(action([EmailSettingsController::class, 'store']), [
        'fromEmail' => '$EMAIL_SETTINGS_FROM_EMAIL',
        'fromName' => 'Test Sender',
    ])->assertSessionHasErrors('fromEmail');
});

it('fails required validation for missing from email environment variables', function () {
    post(action([EmailSettingsController::class, 'store']), [
        'fromEmail' => '$EMAIL_SETTINGS_MISSING',
        'fromName' => 'Test Sender',
    ])->assertSessionHasErrors('fromEmail');
});

it('fails required validation for missing sender name environment variables', function () {
    post(action([EmailSettingsController::class, 'store']), [
        'fromEmail' => 'test@example.com',
        'fromName' => '$EMAIL_SETTINGS_FROM_NAME',
    ])->assertSessionHasErrors('fromName');
});

it('can save email settings with an environment variable mailer', function () {
    putenv('EMAIL_SETTINGS_MAILER=envsmtp');
    config(['mail.mailers.envsmtp' => ['transport' => 'smtp']]);

    post(action([EmailSettingsController::class, 'store']), [
        'fromEmail' => 'test@example.com',
        'fromName' => 'Test Sender',
        'mailer' => '$EMAIL_SETTINGS_MAILER',
    ])->assertRedirectBack()
        ->assertSessionHasNoErrors();

    expect(ProjectConfig::get('email')['mailer'])->toBe('$EMAIL_SETTINGS_MAILER');
});

it('validates resolved environment variable mailer values', function () {
    putenv('EMAIL_SETTINGS_MAILER=missing-mailer');

    post(action([EmailSettingsController::class, 'store']), [
        'fromEmail' => 'test@example.com',
        'fromName' => 'Test Sender',
        'mailer' => '$EMAIL_SETTINGS_MAILER',
    ])->assertSessionHasErrors('mailer');
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

it('can save email settings with environment variable site overrides', function () {
    putenv('EMAIL_SETTINGS_SITE_EMAIL=site@example.com');
    $site = Sites::getPrimarySite();

    post(action([EmailSettingsController::class, 'store']), [
        'fromEmail' => 'default@example.com',
        'fromName' => 'Default Sender',
        'siteOverrides' => [
            $site->uid => [
                'fromEmail' => '$EMAIL_SETTINGS_SITE_EMAIL',
            ],
        ],
    ])->assertRedirectBack()
        ->assertSessionHasNoErrors();

    expect(ProjectConfig::get('email')['siteOverrides'][$site->uid]['fromEmail'])->toBe('$EMAIL_SETTINGS_SITE_EMAIL');
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
