<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mail\transportadapters;

use Craft;
use craft\behaviors\EnvAttributeParserBehavior;

/**
 * Smtp implements a Gmail transport adapter into Craft’s mailer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Gmail extends BaseTransportAdapter
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'Gmail';
    }

    // Properties
    // =========================================================================

    /**
     * @var string|null The username that should be used
     */
    public $username;

    /**
     * @var string|null The password that should be used
     */
    public $password;

    /**
     * @var string The timeout duration (in seconds)
     */
    public $timeout = 10;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'parser' => [
                'class' => EnvAttributeParserBehavior::class,
                'attributes' => [
                    'username',
                    'password',
                ],
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
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
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['username', 'password'], 'trim'];
        $rules[] = [['username', 'password', 'timeout'], 'required'];
        $rules[] = [['timeout'], 'number', 'integerOnly' => true];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('_components/mailertransportadapters/Gmail/settings', [
            'adapter' => $this
        ]);
    }

    /**
     * @inheritdoc
     */
    public function defineTransport()
    {
        return [
            'class' => \Swift_SmtpTransport::class,
            'host' => 'smtp.gmail.com',
            'port' => 465,
            'encryption' => 'ssl',
            'username' => Craft::parseEnv($this->username),
            'password' => Craft::parseEnv($this->password),
            'timeout' => $this->timeout,
        ];
    }
}
