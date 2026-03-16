<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\mail\Mailer;
use craft\mail\transportadapters\Gmail;
use craft\mail\transportadapters\Sendmail;
use craft\mail\transportadapters\Smtp;
use craft\mail\transportadapters\TransportAdapterInterface;
use CraftCms\Cms\Component\Exceptions\MissingComponentException;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Facades\Security;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\User\Elements\User;
use yii\base\Event;
use yii\base\Model;
use function CraftCms\Cms\t;

/**
 * Class MailerHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use Laravel mail configuration and drivers.
 */
class MailerHelper
{
    /**
     * @event RegisterComponentTypesEvent The event that is triggered when registering mailer transport adapter types.
     *
     * Mailer transports must implement [[TransportAdapterInterface]]. [[BaseTransportAdapter]] provides a base implementation.
     *
     * As of Craft 6, custom transport registration is ignored and Laravel mail drivers are used instead.
     * ---
     * ```php
     * use craft\events\RegisterComponentTypesEvent;
     * use craft\helpers\MailerHelper;
     * use yii\base\Event;
     *
     * Event::on(MailerHelper::class,
     *     MailerHelper::EVENT_REGISTER_MAILER_TRANSPORTS,
     *     function(RegisterComponentTypesEvent $event) {
     *         $event->types[] = MyTransportType::class;
     *     }
     * );
     * ```
     */
    public const EVENT_REGISTER_MAILER_TRANSPORTS = 'registerMailerTransports';

    /**
     * Returns all available mailer transport adapter classes.
     *
     * @return string[]
     * @phpstan-return class-string<TransportAdapterInterface>[]
     */
    public static function allMailerTransportTypes(): array
    {
        $transportTypes = [
            Sendmail::class,
            Smtp::class,
            Gmail::class,
        ];

        if (Event::hasHandlers(self::class, self::EVENT_REGISTER_MAILER_TRANSPORTS)) {
            self::_warnIgnoredTransports();
        }

        return $transportTypes;
    }

    /**
     * Creates a transport adapter based on the given mail settings.
     *
     * @param class-string<TransportAdapterInterface> $type
     * @param array|null $settings
     * @return TransportAdapterInterface
     * @throws MissingComponentException if $type is missing
     */
    public static function createTransportAdapter(string $type, ?array $settings = null): TransportAdapterInterface
    {
        if (!in_array($type, self::allMailerTransportTypes(), true)) {
            throw self::_unsupportedTransportException($type);
        }

        try {
            $component = Component::createComponent([
                'type' => $type,
            ], TransportAdapterInterface::class);
        } catch (MissingComponentException) {
            throw self::_unsupportedTransportException($type);
        }

        if ($settings) {
            if ($component instanceof Model) {
                $component->setAttributes($settings, false);
            } else {
                Typecast::configure($component, $settings);
            }
        }

        return $component;
    }

    /**
     * Normalizes To/From/CC/BCC values into an array of email addresses, or email/name pairs.
     *
     * @param string|array|User|User[]|null $emails
     * @return array
     * @since 3.5.0
     */
    public static function normalizeEmails(mixed $emails): array
    {
        if (empty($emails)) {
            return [];
        }

        if (!is_array($emails)) {
            $emails = [$emails];
        }

        $normalized = [];

        foreach ($emails as $key => $value) {
            if ($value instanceof User) {
                if ($value->fullName !== null) {
                    $normalized[$value->email] = $value->fullName;
                } else {
                    $normalized[] = $value->email;
                }
            } elseif (is_numeric($key)) {
                $normalized[] = $value;
            } elseif ($value !== null) {
                $normalized[$key] = $value;
            } else {
                $normalized[] = $key;
            }
        }

        return $normalized;
    }

    /**
     * Returns a report of the settings used for the given Mailer instance.
     *
     * @param Mailer $mailer
     * @param TransportAdapterInterface|null $transportAdapter
     * @return string
     * @since 3.5.0
     */
    public static function settingsReport(Mailer $mailer, ?TransportAdapterInterface $transportAdapter = null): string
    {
        $defaultMailer = data_get(config('mail'), 'default', 'default');
        $mailerConfig = data_get(config('mail'), sprintf('mailers.%s', $defaultMailer), []);
        $transportType = is_array($mailerConfig)
            ? ($mailerConfig['transport'] ?? $defaultMailer)
            : $defaultMailer;

        $settings = [
            t('From') => self::_emailList($mailer->from),
            t('Reply To') => self::_emailList($mailer->replyTo),
            t('Template') => $mailer->template,
            t('Transport Type') => $transportType,
            t('Mailer') => $defaultMailer,
        ];

        foreach (['host', 'port', 'encryption', 'scheme', 'username', 'path', 'url'] as $configKey) {
            $value = is_array($mailerConfig) ? ($mailerConfig[$configKey] ?? null) : null;

            if (is_scalar($value)) {
                $settings[ucfirst((string)$configKey)] = Security::redactIfSensitive($configKey, $value);
            } elseif (is_array($value)) {
                $settings[ucfirst((string)$configKey)] = 'Array';
            } elseif (is_object($value)) {
                $settings[ucfirst((string)$configKey)] = 'Object';
            }
        }

        $report = '';
        foreach ($settings as $label => $value) {
            $report .= "- **$label:** $value\n";
        }

        return $report;
    }

    /**
     * Normalizes a list of emails and returns them in a comma-separated list.
     *
     * @param mixed $emails
     * @return string
     */
    private static function _emailList(mixed $emails): string
    {
        $normalized = static::normalizeEmails($emails);
        if (empty($normalized)) {
            return '';
        }
        $list = [];
        foreach ($normalized as $key => $value) {
            if (is_numeric($key)) {
                $list[] = $value;
            } else {
                $list[] = "$value <$key>";
            }
        }
        return implode(', ', $list);
    }

    private static function _warnIgnoredTransports(): void
    {
        Deprecator::log(
            'MailerHelper::EVENT_REGISTER_MAILER_TRANSPORTS',
            'Custom mailer transport adapters are ignored. Configure Laravel mail drivers instead.',
        );
    }

    private static function _unsupportedTransportException(string $type): MissingComponentException
    {
        return new MissingComponentException(sprintf(
            'Mailer transport adapter "%s" is no longer supported. Configure a Laravel mailer/driver in your application config or environment instead.',
            $type,
        ));
    }
}
