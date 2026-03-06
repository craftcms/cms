<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\SystemMessage\SystemMessages;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;

it('renders twig subject, text, and html for system messages', function () {
    $user = UserModel::factory()->createElement();
    $user = User::find()->id($user->id)->one();

    $message = app(SystemMessages::class)
        ->mailable('forgot_password', $user, ['link' => 'https://example.test/reset'])
        ->renderedMessage();

    expect($message->subject)->toBe('Reset your password');
    expect($message->textBody)->toContain('https://example.test/reset');
    expect($message->textBody)->not->toContain('<https://example.test/reset>');
    expect($message->htmlBody)->toContain('<p>');
    expect($message->htmlBody)->toContain('https://example.test/reset');
    expect($message->variables)->toHaveKey('emailKey', 'forgot_password');
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

it('uses the site language when rendering from a site context', function () {
    $site = Sites::getPrimarySite();
    $user = UserModel::factory()->createElement();
    $user = User::find()->id($user->id)->one();

    $message = app(SystemMessages::class)
        ->mailable(
            key: 'forgot_password',
            user: $user,
            variables: ['link' => 'https://example.test/reset'],
        )
        ->renderedMessage();

    expect($message->language)->toBe($site->getLanguage());
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
