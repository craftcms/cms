<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\SystemMessage\Mailables\SystemMessageMailable;
use Illuminate\Support\Facades\Mail;

test('sends a test email to the provided recipient', function () {
    Mail::fake();

    $this->artisan('craft:mailer:test --to=test@example.com')
        ->assertSuccessful();

    Mail::assertSent(
        SystemMessageMailable::class,
        fn (SystemMessageMailable $mailable): bool => $mailable->key === 'test_email' &&
            $mailable->hasTo('test@example.com') &&
            isset($mailable->variables['settings']),
    );
});

test('prompts for recipient email when no recipient option is provided', function () {
    Mail::fake();

    $this->artisan('craft:mailer:test')
        ->expectsQuestion('Which email address should the test email be sent to?', 'prompted@example.com')
        ->assertSuccessful();

    Mail::assertSent(
        SystemMessageMailable::class,
        fn (SystemMessageMailable $mailable): bool => $mailable->hasTo('prompted@example.com'),
    );
});

test('requires a recipient in non-interactive mode', function () {
    $this->artisan('craft:mailer:test --no-interaction')
        ->assertExitCode(1);
});

test('sends a test email with site-specific overrides', function () {
    Mail::fake();

    $site = Sites::getPrimarySite();

    ProjectConfig::set('email', [
        'fromEmail' => 'default@example.com',
        'fromName' => 'Default Sender',
        'siteOverrides' => [
            $site->uid => [
                'fromEmail' => 'site@example.com',
                'fromName' => 'Site Sender',
            ],
        ],
    ]);

    $this->artisan("craft:mailer:test --to=test@example.com --site={$site->handle}")
        ->assertSuccessful();

    Mail::assertSent(
        SystemMessageMailable::class,
        fn (SystemMessageMailable $mailable): bool => $mailable->hasTo('test@example.com') &&
            $mailable->siteId === $site->id,
    );
});

test('fails with an invalid site handle', function () {
    $this->artisan('craft:mailer:test --to=test@example.com --site=nonexistent')
        ->expectsOutputToContain('Invalid site handle: nonexistent')
        ->assertExitCode(1);
});
