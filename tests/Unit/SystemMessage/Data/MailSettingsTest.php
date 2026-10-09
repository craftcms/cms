<?php

declare(strict_types=1);

use CraftCms\Cms\Email\Data\MailSettings;

test('creates from array with all values', function () {
    $settings = MailSettings::fromArray([
        'fromEmail' => 'site@example.com',
        'fromName' => 'Site Mailer',
        'replyToEmail' => 'reply@example.com',
        'mailer' => 'smtp',
    ]);

    expect($settings->fromEmail)->toBe('site@example.com')
        ->and($settings->fromName)->toBe('Site Mailer')
        ->and($settings->replyToEmail)->toBe('reply@example.com')
        ->and($settings->mailer)->toBe('smtp');
});

test('creates from array with partial values', function () {
    $settings = MailSettings::fromArray([
        'fromEmail' => 'site@example.com',
    ]);

    expect($settings->fromEmail)->toBe('site@example.com')
        ->and($settings->fromName)->toBeNull()
        ->and($settings->replyToEmail)->toBeNull()
        ->and($settings->mailer)->toBeNull();
});

test('creates from empty array', function () {
    $settings = MailSettings::fromArray([]);

    expect($settings->fromEmail)->toBeNull()
        ->and($settings->fromName)->toBeNull()
        ->and($settings->replyToEmail)->toBeNull()
        ->and($settings->mailer)->toBeNull();
});

test('resolves env values', function () {
    putenv('TEST_FROM_EMAIL=resolved@example.com');

    $settings = MailSettings::fromArray([
        'fromEmail' => '$TEST_FROM_EMAIL',
    ]);

    expect($settings->resolvedFromEmail())->toBe('resolved@example.com')
        ->and($settings->fromEmail)->toBe('$TEST_FROM_EMAIL');

    putenv('TEST_FROM_EMAIL');
});

test('returns null for unset resolved values', function () {
    $settings = MailSettings::fromArray([]);

    expect($settings->resolvedFromEmail())->toBeNull()
        ->and($settings->resolvedFromName())->toBeNull()
        ->and($settings->resolvedReplyToEmail())->toBeNull();
});

test('converts to array filtering empty values', function () {
    $settings = MailSettings::fromArray([
        'fromEmail' => 'site@example.com',
        'fromName' => null,
        'replyToEmail' => 'reply@example.com',
    ]);

    expect($settings->toArray())->toBe([
        'fromEmail' => 'site@example.com',
        'replyToEmail' => 'reply@example.com',
    ]);
});

test('converts to empty array when all values are null', function () {
    $settings = MailSettings::fromArray([]);

    expect($settings->toArray())->toBe([]);
});
