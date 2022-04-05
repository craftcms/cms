<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\elements\User;
use craft\errors\MissingComponentException;
use craft\events\RegisterComponentTypesEvent;
use craft\mail\Mailer;
use craft\mail\transportadapters\BaseTransportAdapter;
use craft\mail\transportadapters\Gmail;
use craft\mail\transportadapters\Sendmail;
use craft\mail\transportadapters\Smtp;
use craft\mail\transportadapters\TransportAdapterInterface;
use yii\base\Event;
use yii\helpers\Inflector;

/**
 * Class MailerHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class MailerHelper
{
    /**
     * @event RegisterComponentTypesEvent The event that is triggered when registering mailer transport adapter types.
     *
     * Mailer transports must implement [[TransportAdapterInterface]]. [[BaseTransportAdapter]] provides a base implementation.
     * ---
     * ```php
     * use craft\events\RegisterComponentTypesEvent;
     * use craft\helpers\MailerHelper;
     * use yii\base\Event;
     *
     * Event::on(MailerHelper::class,
     *     MailerHelper::EVENT_REGISTER_MAILER_TRANSPORT_TYPES,
     *     function(RegisterComponentTypesEvent $event) {
     *         $event->types[] = MyTransportType::class;
     *     }
     * );
     * ```
     */
    public const EVENT_REGISTER_MAILER_TRANSPORT_TYPES = 'registerMailerTransportTypes';

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

        $event = new RegisterComponentTypesEvent([
            'types' => $transportTypes,
        ]);
        Event::trigger(static::class, self::EVENT_REGISTER_MAILER_TRANSPORT_TYPES, $event);

        return $event->types;
    }

    /**
     * Creates a transport adapter based on the given mail settings.
     *
     * @template T of TransportAdapterInterface
     * @param string $type
     * @phpstan-param class-string<T> $type
     * @param array|null $settings
     * @return T
     * @throws MissingComponentException if $type is missing
     */
    public static function createTransportAdapter(string $type, ?array $settings = null): TransportAdapterInterface
    {
        return Component::createComponent([
            'type' => $type,
            'settings' => $settings,
        ], TransportAdapterInterface::class);
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
            } else {
                $normalized[$key] = $value;
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
        $transport = $mailer->getTransport();
        $settings = [
            Craft::t('app', 'From') => self::_emailList($mailer->from),
            Craft::t('app', 'Reply To') => self::_emailList($mailer->replyTo),
            Craft::t('app', 'Template') => $mailer->template,
            Craft::t('app', 'Transport Type') => get_class($transport),
        ];

        $transportSettings = [];
        $security = Craft::$app->getSecurity();

        // Use the transport adapter settings if it was sent
        /** @var BaseTransportAdapter|null $transportAdapter */
        if ($transportAdapter !== null) {
            /** @var BaseTransportAdapter $transportAdapter */
            $settingsAttributes = $transportAdapter->settingsAttributes();
            foreach ($settingsAttributes as $name) {
                $transportSettings[$transportAdapter->getAttributeLabel($name)] = $transportAdapter->$name;
            }
        } else {
            // Otherwise just output whatever public properties we have available on the transport
            /** @var array $asArray */
            $asArray = (array)$transportAdapter;
            foreach ($asArray as $name => $value) {
                $transportSettings[Inflector::camel2words($name, true)] = $value;
            }
        }

        foreach ($transportSettings as $label => $value) {
            if (is_scalar($value)) {
                $settings[$label] = $security->redactIfSensitive($label, $value);
            } elseif (is_array($value)) {
                $settings[$label] = 'Array';
            } elseif (is_object($value)) {
                $settings[$label] = 'Object';
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
}
