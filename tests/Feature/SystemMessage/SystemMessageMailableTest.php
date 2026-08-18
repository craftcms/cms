<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\SystemMessage\SystemMessages;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;

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
    config(['view.paths' => [dirname(__DIR__, 2).'/Support/templates']]);
    ProjectConfig::set('email', array_merge(
        ProjectConfig::get('email') ?? [],
        ['template' => 'mail/custom-system-message.twig'],
    ));

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

it('renders system messages through a site-specific template override', function () {
    $site = Sites::getPrimarySite();

    config(['view.paths' => [dirname(__DIR__, 2).'/Support/templates']]);
    ProjectConfig::set('email', array_merge(
        ProjectConfig::get('email') ?? [],
        [
            'template' => 'mail/custom-system-message.twig',
            'siteOverrides' => [
                $site->uid => [
                    'template' => 'mail/site-specific-message.twig',
                ],
            ],
        ],
    ));

    $user = UserModel::factory()->createElement([
        'affiliatedSiteId' => $site->id,
    ]);
    $user = User::find()->id($user->id)->one();

    $mailable = app(SystemMessages::class)
        ->mailable('forgot_password', $user, ['link' => 'https://example.test/reset']);

    $html = $mailable->render();

    // Should use the site-specific template, not the global one
    expect($html)->toContain('site-specific-message')
        ->and($html)->not->toContain('custom-system-message')
        ->and($html)->not->toContain('<table class="wrapper"');
});

it('falls back to the global template when no site-specific override is set', function () {
    $site = Sites::getPrimarySite();

    config(['view.paths' => [dirname(__DIR__, 2).'/Support/templates']]);
    ProjectConfig::set('email', array_merge(
        ProjectConfig::get('email') ?? [],
        [
            'template' => 'mail/custom-system-message.twig',
            'siteOverrides' => [
                $site->uid => [
                    'fromEmail' => 'override@example.com',
                    // No template override — should fall back to the global one
                ],
            ],
        ],
    ));

    $user = UserModel::factory()->createElement([
        'affiliatedSiteId' => $site->id,
    ]);
    $user = User::find()->id($user->id)->one();

    $mailable = app(SystemMessages::class)
        ->mailable('forgot_password', $user, ['link' => 'https://example.test/reset']);

    $html = $mailable->render();

    // Should fall back to the global custom template
    expect($html)->toContain('custom-system-message')
        ->and($html)->not->toContain('site-specific-message')
        ->and($html)->not->toContain('<table class="wrapper"');
});

it('uses the default markdown template when no template is configured', function () {
    ProjectConfig::set('email', array_merge(
        ProjectConfig::get('email') ?? [],
        ['template' => null],
    ));

    $user = UserModel::factory()->createElement();
    $user = User::find()->id($user->id)->one();

    $mailable = app(SystemMessages::class)
        ->mailable('forgot_password', $user, ['link' => 'https://example.test/reset']);

    $html = $mailable->render();

    // Should use the default markdown template (contains the wrapper table)
    expect($html)->toContain('<table class="wrapper"')
        ->and($html)->not->toContain('custom-system-message')
        ->and($html)->not->toContain('site-specific-message');
});
