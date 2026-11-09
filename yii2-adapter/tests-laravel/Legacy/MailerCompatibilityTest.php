<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Facades\ProjectConfig;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

it('sends legacy mail through the mailer selected in the email settings', function() {
    Config::set('mail.default', 'array');
    Config::set('mail.mailers.selected', ['transport' => 'array']);
    ProjectConfig::set('email', array_merge(ProjectConfig::get('email') ?? [], ['mailer' => 'selected']));

    $sent = Craft::$app->getMailer()->compose()
        ->setFrom('from@example.com')
        ->setTo('to@example.com')
        ->setSubject('Legacy mail')
        ->setTextBody('Hello')
        ->send();

    expect($sent)->toBeTrue()
        ->and(Mail::mailer('selected')->getSymfonyTransport()->messages())->toHaveCount(1)
        ->and(Mail::mailer('array')->getSymfonyTransport()->messages())->toHaveCount(0);
});

it('sends legacy mail from the sender in the email settings', function() {
    Config::set('mail.default', 'array');
    Config::set('mail.from', ['address' => 'laravel@example.com', 'name' => 'Laravel']);
    ProjectConfig::set('email', array_merge(ProjectConfig::get('email') ?? [], [
        'fromEmail' => 'system@example.com',
        'fromName' => 'System',
        'replyToEmail' => 'reply@example.com',
    ]));

    Craft::$app->getMailer()->compose()
        ->setTo('to@example.com')
        ->setSubject('Legacy mail')
        ->setTextBody('Hello')
        ->send();

    $email = Mail::mailer('array')->getSymfonyTransport()->messages()->first()->getOriginalMessage();

    expect($email->getFrom()[0]->toString())->toBe('"System" <system@example.com>')
        ->and($email->getReplyTo()[0]->getAddress())->toBe('reply@example.com');
});
