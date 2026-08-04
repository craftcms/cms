<?php

declare(strict_types=1);

namespace CraftCms\Cms\Email\Actions;

use CraftCms\Cms\Email\Data\EmailSettings;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Security;
use CraftCms\Cms\SystemMessage\SystemMessages;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Mail;

use function CraftCms\Cms\t;

readonly class SendTestMailAction
{
    public function __construct(
        private SystemMessages $systemMessages,
    ) {}

    public function handle(string $to, ?int $siteId = null): void
    {
        $report = collect($this->settings($siteId))
            ->map(fn (string $value, string $label) => "- **$label:** $value")
            ->implode("\n");

        $user = new User(['username' => $to, 'email' => $to]);

        if ($siteId !== null) {
            $user->affiliatedSiteId = $siteId;
        }

        $mailable = $this->systemMessages->mailable(
            key: 'test_email',
            user: $user,
            variables: [
                'settings' => $report,
            ])->to($to);

        $resolved = EmailSettings::fromProjectConfig()->resolveForSite($siteId);

        Mail::mailer($resolved->mailer)->sendNow($mailable);
    }

    /**
     * @return array<string, string>
     */
    public function settings(?int $siteId = null): array
    {
        $defaultMailer = config('mail.default', 'smtp');

        $resolved = EmailSettings::fromProjectConfig()->resolveForSite($siteId);

        $effectiveMailer = $resolved->mailer ?? $defaultMailer;
        $mailerConfig = config("mail.mailers.{$effectiveMailer}", []);

        $settings = [
            t('Mailer') => $effectiveMailer,
            t('From Address') => $resolved->fromEmail ?? Env::configValue('mail.from.address', fallbackEnvs: ['MAIL_FROM_ADDRESS', 'FROM_EMAIL_ADDRESS']) ?? '',
            t('From Name') => $resolved->fromName ?? Env::configValue('mail.from.name', fallbackEnvs: ['MAIL_FROM_NAME', 'FROM_EMAIL_NAME']) ?? '',
        ];

        foreach (['host', 'port', 'encryption', 'username', 'password'] as $configKey) {
            $value = is_array($mailerConfig) ? ($mailerConfig[$configKey] ?? null) : null;
            $value = Security::redactIfSensitive($configKey, $value);

            if (! is_scalar($value)) {
                continue;
            }

            $settings[ucfirst($configKey)] = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
        }

        return $settings;
    }
}
