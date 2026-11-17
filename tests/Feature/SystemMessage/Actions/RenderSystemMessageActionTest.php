<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\SystemMessage\Actions\RenderSystemMessageAction;
use CraftCms\Cms\SystemMessage\Models\SystemMessage;
use CraftCms\Cms\SystemMessage\SystemMessages;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;

use function CraftCms\Cms\renderSandboxedString;

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

it('escapes variables in the html body but not in the text body', function () {
    $user = UserModel::factory()->createElement();
    $user = User::find()->id($user->id)->one();

    $mailable = app(SystemMessages::class)->mailable('comment_mention', $user, [
        'author' => "O'Brien <b>&",
        'subject' => 'Entry',
        'comment' => 'Comment',
        'link' => 'https://example.test/entry',
    ]);

    $message = app(RenderSystemMessageAction::class)->handle(
        key: $mailable->key,
        variables: $mailable->variables,
        language: $mailable->language,
        siteId: $mailable->siteId,
    );

    expect($message->textBody)->toContain("O'Brien <b>&")
        ->and($message->htmlBody)->toContain('&lt;b&gt;&amp;')
        ->and($message->htmlBody)->not->toContain('<b>');
});

it('suggests only variables that render for the message', function (string $key, Closure $variables, bool $hasUser) {
    $systemMessages = app(SystemMessages::class);
    $systemMessages->register('contact_submission', fn () => new SystemMessage([
        'key' => 'contact_submission',
        'heading' => 'When someone submits the contact form:',
        'subject' => 'New message',
        'body' => '{{ message }}',
        'variables' => ['message' => 'Message'],
    ]));

    $rendered = app(RenderSystemMessageAction::class)->handle($key, $variables());
    $suggested = array_keys($systemMessages->getTemplateVariables($systemMessages->getMessage($key)));

    expect(in_array('user.friendlyName', $suggested, true))->toBe($hasUser);

    foreach ($suggested as $name) {
        expect(renderSandboxedString("{{ $name }}", $rendered->variables))->toBeString();
    }
})->with([
    'message sent without a user' => ['contact_submission', fn () => ['message' => 'Hello'], false],
    'message sent to a user' => ['forgot_password', fn () => app(SystemMessages::class)->mailable(
        'forgot_password',
        User::find()->id(UserModel::factory()->createElement()->id)->one(),
        ['link' => 'https://example.test/reset'],
    )->variables, true],
]);
