<?php

declare(strict_types=1);

use CraftCms\Cms\Email\Mailables\CraftMailable;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\Sites;

it('applies email settings from project config with site overrides', function () {
    $site = Sites::getPrimarySite();

    ProjectConfig::set('email', array_merge(
        ProjectConfig::get('email') ?? [],
        [
            'fromEmail' => 'global@example.com',
            'fromName' => 'Global Sender',
            'replyToEmail' => 'reply@example.com',
            'mailer' => 'postmark',
            'siteOverrides' => [
                $site->uid => [
                    'fromEmail' => 'site@example.com',
                    'fromName' => 'Site Sender',
                    'replyToEmail' => 'site-reply@example.com',
                ],
            ],
        ],
    ));

    $withoutSite = new TestMailable;
    $withoutSite->render();

    expect($withoutSite)
        ->hasFrom('global@example.com', 'Global Sender')->toBeTrue()
        ->hasReplyTo('reply@example.com')->toBeTrue()
        ->usesMailer('postmark')->toBeTrue();

    $withSite = new TestMailable;
    $withSite->siteId = $site->id;
    $withSite->render();

    expect($withSite)
        ->hasFrom('site@example.com', 'Site Sender')->toBeTrue()
        ->hasReplyTo('site-reply@example.com')->toBeTrue()
        ->usesMailer('postmark')->toBeTrue();
});

it('applies email settings only once even when rendered multiple times', function () {
    ProjectConfig::set('email', array_merge(
        ProjectConfig::get('email') ?? [],
        [
            'fromEmail' => 'from@example.com',
            'fromName' => 'Sender',
        ],
    ));

    $mailable = new TestMailable;
    $mailable->render();
    $mailable->render();

    expect($mailable->from)->toHaveCount(1)
        ->and($mailable)->hasFrom('from@example.com', 'Sender')->toBeTrue();
});

class TestMailable extends CraftMailable
{
    public function build(): static
    {
        return $this
            ->subject('Test Subject')
            ->html('<p>Test email body</p>');
    }
}
