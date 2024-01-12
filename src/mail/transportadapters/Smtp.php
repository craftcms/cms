<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mail\transportadapters;

use Craft;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\helpers\App;
use Symfony\Component\Mailer\Transport\AbstractTransport;

/**
 * Smtp implements a SMTP transport adapter into Craft’s mailer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Smtp extends BaseTransportAdapter
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'SMTP';
    }

    /**
     * @var string|null The host that should be used
     */
    public ?string $host = null;

    /**
     * @var string|null The port that should be used
     */
    public ?string $port = null;

    /**
     * @var bool|string|null Whether to use authentication
     */
    public bool|string|null $useAuthentication = null;

    /**
     * @var string|null The username that should be used
     */
    public ?string $username = null;

    /**
     * @var string|null The password that should be used
     */
    public ?string $password = null;

    /**
     * @var string|null The encryption method that should be used, if any (ssl or tls)
     * @deprecated in 4.3.7. All SMTP requests will use TLS whenever port 465 is used, or the port isn’t specified and OpenSSL is installed.
     */
    public ?string $encryptionMethod = null;

    /**
     * @var string|int The timeout duration (in seconds)
     * @deprecated in 4.3.7.
     */
    public string|int $timeout = 10;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // Config normalization
        if (($config['useAuthentication'] ?? null) === '') {
            unset($config['useAuthentication']);
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    protected function defineBehaviors(): array
    {
        return [
            'parser' => [
                'class' => EnvAttributeParserBehavior::class,
                'attributes' => [
                    'host',
                    'port',
                    'useAuthentication',
                    'username',
                    'password',
                    'encryptionMethod',
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'host' => Craft::t('app', 'Hostname'),
            'port' => Craft::t('app', 'Port'),
            'useAuthentication' => Craft::t('app', 'Use authentication'),
            'username' => Craft::t('app', 'Username'),
            'password' => Craft::t('app', 'Password'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['host'], 'trim'];
        $rules[] = [['host'], 'required'];
        $rules[] = [
            ['username', 'password'],
            'required',
            'when' => function($model) {
                /** @var self $model */
                return App::parseBooleanEnv($model->useAuthentication) ?? false;
            },
        ];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('_components/mailertransportadapters/Smtp/settings.twig', [
            'adapter' => $this,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function defineTransport(): array|AbstractTransport
    {
        $config = [
            'scheme' => 'smtp',
            'host' => App::parseEnv($this->host),
            'port' => App::parseEnv($this->port) ?: 0,
        ];

        if (App::parseBooleanEnv($this->useAuthentication) ?? false) {
            $config['username'] = App::parseEnv($this->username);
            $config['password'] = App::parseEnv($this->password);
        }

        return $config;
    }
}
