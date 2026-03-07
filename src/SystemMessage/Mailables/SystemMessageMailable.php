<?php

declare(strict_types=1);

namespace CraftCms\Cms\SystemMessage\Mailables;

use CraftCms\Cms\SystemMessage\Actions\RenderSystemMessageAction;
use CraftCms\Cms\SystemMessage\Data\RenderedSystemMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

final class SystemMessageMailable extends Mailable
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

        $data = array_merge($message->variables, [
            'textBody' => $message->textBody,
            'htmlBody' => $message->htmlBody,
        ]);

        return $this
            ->subject($message->subject)
            ->markdown('mail.system-message', $data)
            ->text('mail.system-message-text', $data);
    }
}
