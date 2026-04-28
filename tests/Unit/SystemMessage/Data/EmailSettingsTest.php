<?php

declare(strict_types=1);

use CraftCms\Cms\Email\Data\EmailSettings;
use CraftCms\Cms\Email\Data\MailSettings;

test('constructs with defaults', function () {
    $settings = new EmailSettings;

    expect($settings->fromEmail)->toBeNull()
        ->and($settings->fromName)->toBeNull()
        ->and($settings->replyToEmail)->toBeNull()
        ->and($settings->mailer)->toBeNull()
        ->and($settings->template)->toBeNull()
        ->and($settings->siteOverrides)->toBe([]);
});

test('constructs with all values', function () {
    $override = new MailSettings(fromEmail: 'site@example.com');

    $settings = new EmailSettings(
        fromEmail: 'default@example.com',
        fromName: 'Default Sender',
        replyToEmail: 'reply@example.com',
        mailer: 'smtp',
        template: '_emails/layout',
        siteOverrides: ['site-uid-1' => $override],
    );

    expect($settings->fromEmail)->toBe('default@example.com')
        ->and($settings->fromName)->toBe('Default Sender')
        ->and($settings->replyToEmail)->toBe('reply@example.com')
        ->and($settings->mailer)->toBe('smtp')
        ->and($settings->template)->toBe('_emails/layout')
        ->and($settings->siteOverrides)->toHaveKey('site-uid-1');
});

test('creates from project config array', function () {
    $config = [
        'fromEmail' => 'config@example.com',
        'fromName' => 'Config Sender',
        'replyToEmail' => 'config-reply@example.com',
        'mailer' => 'ses',
        'template' => '_emails/layout',
        'siteOverrides' => [
            'uid-abc' => [
                'fromEmail' => 'site-a@example.com',
                'fromName' => 'Site A',
                'template' => '_emails/site-a-layout',
            ],
        ],
    ];

    $settings = EmailSettings::fromProjectConfig($config);

    expect($settings->fromEmail)->toBe('config@example.com')
        ->and($settings->fromName)->toBe('Config Sender')
        ->and($settings->replyToEmail)->toBe('config-reply@example.com')
        ->and($settings->mailer)->toBe('ses')
        ->and($settings->template)->toBe('_emails/layout')
        ->and($settings->siteOverrides)->toHaveCount(1)
        ->and($settings->siteOverrides['uid-abc'])->toBeInstanceOf(MailSettings::class)
        ->and($settings->siteOverrides['uid-abc']->fromEmail)->toBe('site-a@example.com')
        ->and($settings->siteOverrides['uid-abc']->template)->toBe('_emails/site-a-layout');
});

test('creates from empty project config', function () {
    $settings = EmailSettings::fromProjectConfig([]);

    expect($settings->fromEmail)->toBeNull()
        ->and($settings->fromName)->toBeNull()
        ->and($settings->siteOverrides)->toBe([]);
});

test('resolves settings without site returns defaults with env fallback', function () {
    config()->set('mail.from.address', 'env@example.com');
    config()->set('mail.from.name', 'Env Sender');

    $settings = new EmailSettings;
    $resolved = $settings->resolveForSite();

    expect($resolved)->toBeInstanceOf(MailSettings::class)
        ->and($resolved->fromEmail)->toBe('env@example.com')
        ->and($resolved->fromName)->toBe('Env Sender')
        ->and($resolved->mailer)->toBeNull();
});

test('resolves settings with project config values overriding env', function () {
    config()->set('mail.from.address', 'env@example.com');
    config()->set('mail.from.name', 'Env Sender');

    $settings = new EmailSettings(
        fromEmail: 'pc@example.com',
        fromName: 'PC Sender',
    );

    $resolved = $settings->resolveForSite();

    expect($resolved->fromEmail)->toBe('pc@example.com')
        ->and($resolved->fromName)->toBe('PC Sender');
});

test('converts to array filtering empty values', function () {
    $settings = new EmailSettings(
        fromEmail: 'test@example.com',
        fromName: 'Test',
        template: '_emails/layout',
        siteOverrides: [
            'uid-1' => new MailSettings(fromEmail: 'site@example.com'),
            'uid-2' => new MailSettings, // all nulls, should be filtered
        ],
    );

    $array = $settings->toArray();

    expect($array)->toBe([
        'fromEmail' => 'test@example.com',
        'fromName' => 'Test',
        'template' => '_emails/layout',
        'siteOverrides' => [
            'uid-1' => ['fromEmail' => 'site@example.com'],
        ],
    ]);
});

test('converts to array without site overrides when all are empty', function () {
    $settings = new EmailSettings(
        fromEmail: 'test@example.com',
    );

    $array = $settings->toArray();

    expect($array)->toBe([
        'fromEmail' => 'test@example.com',
    ])
        ->and($array)->not->toHaveKey('siteOverrides');
});

test('resolves env variable references in values', function () {
    putenv('TEST_PC_EMAIL=resolved@example.com');
    putenv('TEST_PC_NAME=Resolved Sender');

    $settings = new EmailSettings(
        fromEmail: '$TEST_PC_EMAIL',
        fromName: '$TEST_PC_NAME',
    );

    expect($settings->resolvedFromEmail())->toBe('resolved@example.com')
        ->and($settings->resolvedFromName())->toBe('Resolved Sender');

    putenv('TEST_PC_EMAIL');
    putenv('TEST_PC_NAME');
});

test('resolves template from email settings', function () {
    config()->set('mail.from.address', 'env@example.com');
    config()->set('mail.from.name', 'Env Sender');

    $settings = new EmailSettings(
        fromEmail: 'test@example.com',
        fromName: 'Test',
        template: '_emails/layout',
    );

    $resolved = $settings->resolveForSite();

    expect($resolved->template)->toBe('_emails/layout');
});

test('resolves template as null when not set', function () {
    config()->set('mail.from.address', 'env@example.com');
    config()->set('mail.from.name', 'Env Sender');

    $settings = new EmailSettings(
        fromEmail: 'test@example.com',
        fromName: 'Test',
    );

    $resolved = $settings->resolveForSite();

    expect($resolved->template)->toBeNull();
});

test('resolves env variable in template', function () {
    putenv('TEST_TEMPLATE=_emails/from-env');

    $settings = new EmailSettings(
        template: '$TEST_TEMPLATE',
    );

    expect($settings->resolvedTemplate())->toBe('_emails/from-env');

    putenv('TEST_TEMPLATE');
});

test('site override template resolves env variables', function () {
    putenv('TEST_SITE_TEMPLATE=_emails/site-env');

    $override = new MailSettings(
        template: '$TEST_SITE_TEMPLATE',
    );

    expect($override->resolvedTemplate())->toBe('_emails/site-env');

    putenv('TEST_SITE_TEMPLATE');
});

test('site override includes template in toArray', function () {
    $override = new MailSettings(
        fromEmail: 'site@example.com',
        template: '_emails/site-layout',
    );

    expect($override->toArray())->toBe([
        'fromEmail' => 'site@example.com',
        'template' => '_emails/site-layout',
    ]);
});

test('site override excludes template from toArray when null', function () {
    $override = new MailSettings(
        fromEmail: 'site@example.com',
    );

    expect($override->toArray())->toBe([
        'fromEmail' => 'site@example.com',
    ])->not->toHaveKey('template');
});
