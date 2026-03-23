<?php

declare(strict_types=1);

namespace CraftCms\Cms\SystemMessage\Mailables;

use CraftCms\Cms\Email\Mailables\CraftMailable;
use CraftCms\Cms\SystemMessage\Actions\FormatSystemMessageMailAction;
use CraftCms\Cms\SystemMessage\Actions\RenderSystemMessageAction;
use CraftCms\Cms\SystemMessage\Data\RenderedSystemMessage;

class SystemMessageMailable extends CraftMailable
{
    public function __construct(
        public string $key,
        public array $variables = [],
        public ?string $language = null,
        public ?int $siteId = null,
    ) {}

    public function renderedMessage(): RenderedSystemMessage
    {
        return app(RenderSystemMessageAction::class)->handle(
            key: $this->key,
            variables: $this->variables,
            language: $this->language,
            siteId: $this->siteId,
        );
    }

    public function build(): static
    {
        $resolved = $this->applyEmailSettings();

        $message = $this->renderedMessage();
        $formattedMessage = app(FormatSystemMessageMailAction::class)->handle($message, $resolved);

        $mailable = $this
            ->subject($message->subject)
            ->text('mail.system-message-text', $formattedMessage->viewData);

        if ($formattedMessage->usesCustomTemplate) {
            return $mailable->html($formattedMessage->htmlBody);
        }

        return $mailable->markdown('mail.system-message', $formattedMessage->viewData);
    }
}
