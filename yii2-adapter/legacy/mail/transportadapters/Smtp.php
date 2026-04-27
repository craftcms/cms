<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mail\transportadapters;

use Craft;
use craft\behaviors\EnvAttributeParserBehavior;
use CraftCms\Cms\Support\Env;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use function CraftCms\Cms\t;

/**
 * Smtp implements a SMTP transport adapter into Craft’s mailer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 transport adapters are ignored; use Laravel mail drivers.
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
            'host' => t('Hostname'),
            'port' => t('Port'),
            'useAuthentication' => t('Use authentication'),
            'username' => t('Username'),
            'password' => t('Password'),
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
                Env::parseBoolean($model->useAuthentication) ?? false,
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
        return t('Legacy mail transport adapter settings are ignored. Configure Laravel mail drivers instead.');
    }

    /**
     * @inheritdoc
     */
    public function defineTransport(): array|AbstractTransport
    {
        $config = [
            'scheme' => 'smtp',
            'host' => Env::parse($this->host) ?? '',
            'port' => $this->port ? (int) Env::parse($this->port) : null,
        ];

        if (Env::parseBoolean($this->useAuthentication) ?? false) {
            $config['username'] = Env::parse($this->username);
            $config['password'] = Env::parse($this->password);
        }

        return $config;
    }
}
