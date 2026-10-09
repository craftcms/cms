<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\SystemMessage\Actions\RenderSystemMessageAction;
use CraftCms\Cms\SystemMessage\SystemMessages;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;

it('renders twig subject, text, and html for system messages', function () {
    $user = UserModel::factory()->createElement();
    $user = User::find()->id($user->id)->one();

    $mailable = app(SystemMessages::class)
        ->mailable('forgot_password', $user, ['link' => 'https://example.test/reset']);

    $message = app(RenderSystemMessageAction::class)->handle(
        key: $mailable->key,
        variables: $mailable->variables,
        language: $mailable->language,
        siteId: $mailable->siteId,
    );

    expect($message->subject)->toBe('Reset your password');
    expect($message->textBody)->toContain('https://example.test/reset');
    expect($message->textBody)->not->toContain('<https://example.test/reset>');
    expect($message->htmlBody)->toContain('<p>');
    expect($message->htmlBody)->toContain('https://example.test/reset');
    expect($message->variables)->toHaveKey('emailKey', 'forgot_password');
});

it('uses the site language when rendering from a site context', function () {
    $site = Sites::getPrimarySite();
    $user = UserModel::factory()->createElement();
    $user = User::find()->id($user->id)->one();

    $mailable = app(SystemMessages::class)->mailable(
        key: 'forgot_password',
        user: $user,
        variables: ['link' => 'https://example.test/reset'],
    );

    $message = app(RenderSystemMessageAction::class)->handle(
        key: $mailable->key,
        variables: $mailable->variables,
        language: $mailable->language,
        siteId: $mailable->siteId,
    );

    expect($message->language)->toBe($site->getLanguage());
});
