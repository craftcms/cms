<?php

declare(strict_types=1);

use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\SystemMessage\Mailables\SystemMessageMailable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

test('sends a test email to the provided recipient', function () {
    Mail::fake();

    $site = Sites::getPrimarySite();

    $this->artisan("craft:mailer:test --to=test@example.com --site={$site->handle}")
        ->assertSuccessful();

    Mail::assertSent(
        SystemMessageMailable::class,
        fn (SystemMessageMailable $mailable): bool => $mailable->key === 'test_email' &&
            $mailable->hasTo('test@example.com') &&
            $mailable->siteId === $site->id &&
            isset($mailable->variables['settings']),
    );
});

test('prompts for recipient email when no recipient option is provided', function () {
    Mail::fake();

    $site = Sites::getPrimarySite();

    $this->artisan("craft:mailer:test --site={$site->handle}")
        ->expectsQuestion('Which email address should the test email be sent to?', 'prompted@example.com')
        ->assertSuccessful();

    Mail::assertSent(
        SystemMessageMailable::class,
        fn (SystemMessageMailable $mailable): bool => $mailable->hasTo('prompted@example.com') &&
            $mailable->siteId === $site->id,
    );
});

test('requires a recipient in non-interactive mode', function () {
    $site = Sites::getPrimarySite();

    $this->artisan("craft:mailer:test --site={$site->handle} --no-interaction")
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
        function (SystemMessageMailable $mailable) use ($site): bool {
            $mailable->render();

            return $mailable->hasTo('test@example.com') &&
                $mailable->hasFrom('site@example.com', 'Site Sender') &&
                $mailable->siteId === $site->id;
        },
    );
});

test('prompts for a site when one is not provided', function () {
    Mail::fake();

    $otherSite = Site::factory()->create([
        'handle' => 'otherSite',
        'name' => 'Other Site',
    ]);
    Sites::refreshSites();

    $primarySite = Sites::getPrimarySite();

    $this->artisan('craft:mailer:test --to=test@example.com')
        ->expectsChoice('Choose a site', $otherSite->handle, [
            $primarySite->handle => $primarySite->name,
            $otherSite->handle => $otherSite->name,
        ])
        ->assertSuccessful();

    Mail::assertSent(
        SystemMessageMailable::class,
        fn (SystemMessageMailable $mailable): bool => $mailable->siteId === $otherSite->id,
    );
});

test('prompts for a site when an invalid one is provided', function () {
    Mail::fake();

    $otherSite = Site::factory()->create([
        'handle' => 'otherSite',
        'name' => 'Other Site',
    ]);
    Sites::refreshSites();

    $primarySite = Sites::getPrimarySite();

    $this->artisan('craft:mailer:test --to=test@example.com --site=nonexistent')
        ->expectsChoice('Choose a site', $otherSite->handle, [
            $primarySite->handle => $primarySite->name,
            $otherSite->handle => $otherSite->name,
        ])
        ->assertSuccessful();

    Mail::assertSent(
        SystemMessageMailable::class,
        fn (SystemMessageMailable $mailable): bool => $mailable->siteId === $otherSite->id,
    );
});

test('uses the primary site in non-interactive mode when one is not provided', function () {
    Mail::fake();

    Site::factory()->create([
        'handle' => 'otherSite',
        'name' => 'Other Site',
    ]);
    Sites::refreshSites();

    $primarySite = Sites::getPrimarySite();

    expect(Artisan::call('craft:mailer:test', ['--to' => 'test@example.com', '--no-interaction' => true]))
        ->toBe(0);

    Mail::assertSent(
        SystemMessageMailable::class,
        fn (SystemMessageMailable $mailable): bool => $mailable->siteId === $primarySite->id,
    );
});
