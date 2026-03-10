<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\SystemMessage\SystemMessages;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;

beforeEach(function () {
    $this->originalTemplatesPath = Aliases::get('@templates');
});

afterEach(function () {
    app(GeneralConfig::class)->systemMessageTemplate = null;
    Aliases::set('@templates', $this->originalTemplatesPath);
    app()->forgetScopedInstances();
});

it('renders mail wrapper views from the laravel mailable', function () {
    $user = UserModel::factory()->createElement();
    $user = User::find()->id($user->id)->one();

    $html = app(SystemMessages::class)
        ->mailable('forgot_password', $user, ['link' => 'https://example.test/reset'])
        ->render();

    expect($html)->toContain('https://example.test/reset');
    expect($html)->not->toContain('{{');
});

it('uses the affiliated site when building mail outside site requests', function () {
    $site = Sites::getPrimarySite();
    $user = UserModel::factory()->createElement([
        'affiliatedSiteId' => $site->id,
    ]);
    $user = User::find()->id($user->id)->one();

    $mailable = app(SystemMessages::class)->mailable(
        key: 'forgot_password',
        user: $user,
        variables: ['link' => 'https://example.test/reset'],
    );

    expect($mailable->siteId)->toBe($site->id);
});

it('renders system messages through the configured site template and preserves the plain-text body', function () {
    Aliases::set('@templates', dirname(__DIR__, 2).'/Support/templates');
    app()->forgetScopedInstances();
    app(GeneralConfig::class)->systemMessageTemplate = 'mail/custom-system-message.twig';

    $user = UserModel::factory()->createElement();
    $user = User::find()->id($user->id)->one();

    $mailable = app(SystemMessages::class)
        ->mailable('forgot_password', $user, ['link' => 'https://example.test/reset']);

    $renderedMessage = $mailable->renderedMessage();
    $html = $mailable->render();
    $plainText = view($mailable->build()->textView, $mailable->buildViewData())->render();

    expect($html)->toContain('custom-system-message')
        ->and($html)->toContain($renderedMessage->subject)
        ->and($html)->toContain($renderedMessage->textBody)
        ->and($html)->toContain($renderedMessage->htmlBody)
        ->and($html)->toContain($renderedMessage->key)
        ->and($html)->toContain($renderedMessage->language)
        ->and($html)->toContain($user->email)
        ->and($html)->not->toContain('<table class="wrapper"')
        ->and(trim($plainText))->toBe($renderedMessage->textBody);
});
