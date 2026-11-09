<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Mail;

use craft\helpers\MailerHelper;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Deprecator;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Mail\MailManager;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * Keeps sending email through the transport adapter that Craft 4 and 5 saved in project config, including those that
 * plugins provide, until the site configures a Laravel mailer.
 *
 * The transport is available as the `craft-transport` mailer, which becomes the default mailer unless the site
 * selects a mailer in Settings → Email, sets `MAIL_MAILER`, or changes the default mailer in `config/mail.php`.
 */
readonly class LegacyTransportCompatibility
{
    public const string MAILER = 'craft-transport';

    public function register(Application $app): void
    {
        $app->booted(fn() => $this->registerMailer($app));
    }

    private function registerMailer(Application $app): void
    {
        $email = $app->make(ProjectConfig::class)->get('email');
        $type = is_array($email) ? ($email['transportType'] ?? null) : null;

        if (!is_string($type) || $type === '') {
            return;
        }

        $settings = is_array($email['transportSettings'] ?? null) ? $email['transportSettings'] : null;

        $app->make(MailManager::class)->extend(self::MAILER, fn() => $this->createTransport($type, $settings));

        $config = $app->make(ConfigRepository::class);
        $config->set('mail.mailers.' . self::MAILER, ['transport' => self::MAILER]);

        if (!$this->hasLaravelMailer($config, $email)) {
            $config->set('mail.default', self::MAILER);
        }
    }

    /**
     * Whether the site has chosen a Laravel mailer: in Settings → Email, with `MAIL_MAILER`, or by changing Laravel’s
     * default `log` mailer in `config/mail.php`.
     */
    private function hasLaravelMailer(ConfigRepository $config, array $email): bool
    {
        return !empty($email['mailer'])
            || Env::get('MAIL_MAILER') !== null
            || $config->get('mail.default') !== 'log';
    }

    private function createTransport(string $type, ?array $settings): TransportInterface
    {
        $adapter = MailerHelper::createTransportAdapter($type, $settings);

        Deprecator::log(
            "mailTransportAdapter:$type",
            sprintf(
                'Email is sent through the “%s” transport adapter saved in the project config. Configure a Laravel mailer in `config/mail.php` and set `MAIL_MAILER` instead.',
                $adapter::displayName(),
            ),
        );

        $transport = $adapter->defineTransport();

        if (!is_array($transport)) {
            return $transport;
        }

        $factory = new Transport(iterator_to_array(Transport::getDefaultFactories()));

        if (isset($transport['dsn'])) {
            return $transport['dsn'] instanceof Dsn
                ? $factory->fromDsnObject($transport['dsn'])
                : $factory->fromString($transport['dsn']);
        }

        return $factory->fromDsnObject(new Dsn(
            $transport['scheme'],
            $transport['host'],
            $transport['username'] ?? null,
            $transport['password'] ?? null,
            $transport['port'] ?? null,
            $transport['options'] ?? [],
        ));
    }
}
