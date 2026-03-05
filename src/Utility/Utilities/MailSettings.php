<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Security;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\Utility\Utility;
use Override;

use function CraftCms\Cms\t;

final class MailSettings extends Utility
{
    #[Override]
    public static function displayName(): string
    {
        return t('Email Settings');
    }

    #[Override]
    public static function id(): string
    {
        return 'mail-settings';
    }

    #[Override]
    public static function icon(): string
    {
        return 'envelope';
    }

    #[Override]
    public static function contentHtml(): string
    {
        return Html::tag('MailSettings', attributes: [
            ':settings' => self::settings(),
            'default-to-email' => self::defaultToEmail(),
        ]);
    }

    public static function settings(): array
    {
        $defaultMailer = data_get(config('mail'), 'default', 'default');
        $mailerConfig = data_get(config('mail'), sprintf('mailers.%s', $defaultMailer), []);
        $fromAddress = Env::configValue('mail.from.address', fallbackEnvs: ['MAIL_FROM_ADDRESS', 'FROM_EMAIL_ADDRESS']);
        $fromName = Env::configValue('mail.from.name', fallbackEnvs: ['MAIL_FROM_NAME', 'FROM_EMAIL_NAME']);

        $settings = [
            t('Mailer') => $defaultMailer,
            t('From address') => $fromAddress,
            t('From name') => $fromName,
        ];

        foreach (['host', 'port', 'encryption', 'scheme', 'username', 'password', 'path', 'url'] as $configKey) {
            $value = is_array($mailerConfig) ? ($mailerConfig[$configKey] ?? null) : null;

            $value = Security::redactIfSensitive($configKey, $value);

            if (! is_scalar($value)) {
                continue;
            }

            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } else {
                $value = (string) $value;
            }

            $settings[ucfirst($configKey)] = $value;
        }

        return $settings;
    }

    /**
     * Returns a markdown-formatted report of the current mail settings.
     */
    public static function settingsReport(): string
    {
        $report = '';

        foreach (self::settings() as $label => $value) {
            $report .= "- **$label:** $value\n";
        }

        return $report;
    }

    private static function defaultToEmail(): string
    {
        $user = auth()->user();

        if ($user instanceof UserElement && $user->email) {
            return $user->email;
        }

        return '';
    }
}
