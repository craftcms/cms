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
     * @var int|string|null The port that should be used
     */
    public int|string|null $port = null;

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
            'when' => fn($model) =>
                /** @var self $model */
                App::parseBooleanEnv($model->useAuthentication) ?? false,
        ];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return $this->settingsHtml(false);
    }

    /**
     * @inheritdoc
     */
    public function getReadOnlySettingsHtml(): ?string
    {
        return $this->settingsHtml(true);
    }

    private function settingsHtml(bool $readOnly): string
    {
        return Craft::$app->getView()->renderTemplate('_components/mailertransportadapters/Smtp/settings.twig', [
            'adapter' => $this,
            'readOnly' => $readOnly,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function defineTransport(): array|AbstractTransport
    {
        $config = [
            'scheme' => 'smtp',
            'host' => App::parseEnv($this->host) ?? '',
            'port' => $this->port ? (int) App::parseEnv($this->port) : null,
        ];

        if (App::parseBooleanEnv($this->useAuthentication) ?? false) {
            $config['username'] = App::parseEnv($this->username);
            $config['password'] = App::parseEnv($this->password);
        }

        return $config;
    }
}
