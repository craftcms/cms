<?php

declare(strict_types=1);

namespace CraftCms\Cms\Email\Data;

use CraftCms\Cms\Support\Env;

readonly class MailSettings
{
    public function __construct(
        public ?string $fromEmail = null,
        public ?string $fromName = null,
        public ?string $replyToEmail = null,
        public ?string $mailer = null,
        public ?string $template = null,
    ) {}

    /**
     * @param  array{fromEmail?: ?string, fromName?: ?string, replyToEmail?: ?string, mailer?: ?string, template?: ?string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            fromEmail: $data['fromEmail'] ?? null,
            fromName: $data['fromName'] ?? null,
            replyToEmail: $data['replyToEmail'] ?? null,
            mailer: $data['mailer'] ?? null,
            template: $data['template'] ?? null,
        );
    }

    public function resolvedFromEmail(): ?string
    {
        return Env::parse($this->fromEmail);
    }

    public function resolvedFromName(): ?string
    {
        return Env::parse($this->fromName);
    }

    public function resolvedReplyToEmail(): ?string
    {
        return Env::parse($this->replyToEmail);
    }

    public function resolvedMailer(): ?string
    {
        return Env::parse($this->mailer);
    }

    public function resolvedTemplate(): ?string
    {
        return Env::parse($this->template);
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return array_filter([
            'fromEmail' => $this->fromEmail,
            'fromName' => $this->fromName,
            'replyToEmail' => $this->replyToEmail,
            'mailer' => $this->mailer,
            'template' => $this->template,
        ]);
    }
}
