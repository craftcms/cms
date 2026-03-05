<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\SystemMessage\Mailables\SystemMessageMailable;
use Illuminate\Support\Facades\Mail;

test('sends a test email to the provided recipient', function () {
    Mail::fake();

    $this->artisan('craft:mailer:test --to=test@example.com')
        ->expectsOutputToContain('Sending a test email to test@example.com with the following settings:')
        ->expectsOutputToContain('Email sent successfully! Check your inbox.')
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
    Cms::config()->testToEmailAddress = ['configured@example.com' => 'Configured Recipient'];

    $this->artisan('craft:mailer:test')
        ->expectsQuestion('Which email address should the test email be sent to?', 'prompted@example.com')
        ->expectsOutputToContain('Email sent successfully! Check your inbox.')
        ->assertSuccessful();

    Mail::assertSent(
        SystemMessageMailable::class,
        fn (SystemMessageMailable $mailable): bool => $mailable->hasTo('prompted@example.com'),
    );
});

test('requires a recipient in non-interactive mode', function () {
    $this->artisan('craft:mailer:test --no-interaction')
        ->expectsOutputToContain('Please provide a recipient with the --to option when running non-interactively.')
        ->assertExitCode(1);
});
