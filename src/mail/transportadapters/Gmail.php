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
use Swift_SmtpTransport;

/**
 * Smtp implements a Gmail transport adapter into Craft’s mailer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Gmail extends BaseTransportAdapter
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'Gmail';
    }

    /**
     * @var string|null The username that should be used
     */
    public ?string $username = null;

    /**
     * @var string|null The password that should be used
     */
    public ?string $password = null;

    /**
     * @var string The timeout duration (in seconds)
     */
    public $timeout = 10;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // Config normalization
        foreach (['username', 'password'] as $name) {
            if (($config[$name] ?? null) === '') {
                unset($config[$name]);
            }
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['parser'] = [
            'class' => EnvAttributeParserBehavior::class,
            'attributes' => [
                'username',
                'password',
            ],
        ];
        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'username' => Craft::t('app', 'Username'),
            'password' => Craft::t('app', 'Password'),
            'timeout' => Craft::t('app', 'Timeout'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['username', 'password'], 'trim'];
        $rules[] = [['username', 'password', 'timeout'], 'required'];
        $rules[] = [['timeout'], 'number', 'integerOnly' => true];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('_components/mailertransportadapters/Gmail/settings', [
            'adapter' => $this,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function defineTransport()
    {
        return [
            'class' => Swift_SmtpTransport::class,
            'host' => 'smtp.gmail.com',
            'port' => 465,
            'encryption' => 'ssl',
            'username' => App::parseEnv($this->username),
            'password' => App::parseEnv($this->password),
            'timeout' => $this->timeout,
        ];
    }
}
