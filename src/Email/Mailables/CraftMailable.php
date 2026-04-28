<?php

declare(strict_types=1);

namespace CraftCms\Cms\Email\Mailables;

use CraftCms\Cms\Email\Data\EmailSettings;
use CraftCms\Cms\Email\Data\MailSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class CraftMailable extends Mailable
{
    use Queueable;

    public ?int $siteId = null;

    private bool $emailSettingsApplied = false;

    /**
     * Applies project config email settings (from, replyTo, mailer)
     * resolved for the current site before the mailable is sent.
     */
    #[\Override]
    protected function prepareMailableForDelivery(): void
    {
        $this->applyEmailSettings();

        parent::prepareMailableForDelivery();
    }

    protected function applyEmailSettings(): MailSettings
    {
        $resolved = EmailSettings::fromProjectConfig()->resolveForSite($this->siteId);

        if ($this->emailSettingsApplied) {
            return $resolved;
        }

        $this->emailSettingsApplied = true;

        if ($resolved->fromEmail) {
            $this->from($resolved->fromEmail, $resolved->fromName ?? '');
        }

        if ($resolved->replyToEmail) {
            $this->replyTo($resolved->replyToEmail);
        }

        if ($resolved->mailer) {
            $this->mailer($resolved->mailer);
        }

        return $resolved;
    }
}
