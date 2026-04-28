<?php

declare(strict_types=1);

namespace CraftCms\Cms\Email\Data;

use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\Sites;

readonly class EmailSettings
{
    /**
     * @param  array<string, MailSettings>  $siteOverrides  Keyed by site UID
     */
    public function __construct(
        public ?string $fromEmail = null,
        public ?string $fromName = null,
        public ?string $replyToEmail = null,
        public ?string $mailer = null,
        public ?string $template = null,
        public array $siteOverrides = [],
    ) {}

    public static function fromProjectConfig(?array $config = null): self
    {
        $config ??= ProjectConfig::get('email') ?? [];

        $siteOverrides = [];

        foreach ($config['siteOverrides'] ?? [] as $siteUid => $overrideData) {
            $siteOverrides[$siteUid] = MailSettings::fromArray($overrideData);
        }

        return new self(
            fromEmail: $config['fromEmail'] ?? null,
            fromName: $config['fromName'] ?? null,
            replyToEmail: $config['replyToEmail'] ?? null,
            mailer: $config['mailer'] ?? null,
            template: $config['template'] ?? null,
            siteOverrides: $siteOverrides,
        );
    }

    /**
     * Resolves the effective mail settings for a given site, merging
     * site-specific overrides with the defaults.
     */
    public function resolveForSite(?int $siteId = null): MailSettings
    {
        $fromEmail = $this->resolvedFromEmail();
        $fromName = $this->resolvedFromName();
        $replyToEmail = $this->resolvedReplyToEmail();
        $mailer = $this->resolvedMailer();
        $template = $this->resolvedTemplate();

        if ($siteId !== null) {
            $site = Sites::getSiteById($siteId);

            if ($site !== null && isset($this->siteOverrides[$site->uid])) {
                $override = $this->siteOverrides[$site->uid];

                $fromEmail = $override->resolvedFromEmail() ?? $fromEmail;
                $fromName = $override->resolvedFromName() ?? $fromName;
                $replyToEmail = $override->resolvedReplyToEmail() ?? $replyToEmail;
                $template = $override->resolvedTemplate() ?? $template;
            }
        }

        return new MailSettings(
            fromEmail: $fromEmail ?? Env::configValue('mail.from.address', fallbackEnvs: ['MAIL_FROM_ADDRESS', 'FROM_EMAIL_ADDRESS']),
            fromName: $fromName ?? Env::configValue('mail.from.name', fallbackEnvs: ['MAIL_FROM_NAME', 'FROM_EMAIL_NAME']),
            replyToEmail: $replyToEmail ?? Env::configValue('mail.reply_to.address'),
            mailer: $mailer,
            template: $template,
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

    public function toArray(): array
    {
        $data = array_filter([
            'fromEmail' => $this->fromEmail,
            'fromName' => $this->fromName,
            'replyToEmail' => $this->replyToEmail,
            'mailer' => $this->mailer,
            'template' => $this->template,
        ]);

        $siteOverrides = array_filter(array_map(
            fn (MailSettings $override) => $override->toArray(),
            $this->siteOverrides,
        ));

        if (! empty($siteOverrides)) {
            $data['siteOverrides'] = $siteOverrides;
        }

        return $data;
    }
}
