<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */
namespace craft\app\mail\transportadaptors;

use Craft;

/**
 * Smtp implements a Gmail transport adapter into Craft’s mailer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Gmail extends BaseTransportAdaptor
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName()
    {
        return 'Gmail';
    }

    // Properties
    // =========================================================================

    /**
     * @var string The username that should be used
     */
    public $username;

    /**
     * @var string The password that should be used
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
        return [
            [['username', 'password', 'timeout'], 'required'],
            [['timeout'], 'number', 'integerOnly' => true],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('_components/mailertransportadaptors/Gmail/settings', [
            'adaptor' => $this
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getTransportConfig()
    {
        return [
            'class' => 'Swift_SmtpTransport',
            'host' => 'smtp.gmail.com',
            'port' => 465,
            'encryption' => 'ssl',
            'username' => $this->username,
            'password' => $this->password,
            'timeout' => $this->timeout,
        ];
    }
}
