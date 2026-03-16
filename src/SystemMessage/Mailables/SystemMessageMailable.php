<?php

declare(strict_types=1);

namespace CraftCms\Cms\SystemMessage\Mailables;

use CraftCms\Cms\SystemMessage\Actions\FormatSystemMessageMailAction;
use CraftCms\Cms\SystemMessage\Actions\RenderSystemMessageAction;
use CraftCms\Cms\SystemMessage\Data\RenderedSystemMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class SystemMessageMailable extends Mailable
{
    use Queueable;

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
        $message = $this->renderedMessage();
        $formattedMessage = app(FormatSystemMessageMailAction::class)->handle($message);

        $mailable = $this
            ->subject($message->subject)
            ->text('mail.system-message-text', $formattedMessage->viewData);

        if ($formattedMessage->usesCustomTemplate) {
            return $mailable->html($formattedMessage->htmlBody);
        }

        return $mailable->markdown('mail.system-message', $formattedMessage->viewData);
    }
}
