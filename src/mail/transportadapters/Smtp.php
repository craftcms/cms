<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mail\transportadapters;

use Craft;
use craft\helpers\StringHelper;

/**
 * Smtp implements a SMTP transport adapter into Craft’s mailer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Smtp extends BaseTransportAdapter
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'SMTP';
    }

    // Properties
    // =========================================================================

    /**
     * @var string|null The host that should be used
     */
    public $host;

    /**
     * @var string|null The port that should be used
     */
    public $port;

    /**
     * @var bool|null Whether to use authentication
     */
    public $useAuthentication;

    /**
     * @var string|null The username that should be used
     */
    public $username;

    /**
     * @var string|null The password that should be used
     */
    public $password;

    /**
     * @var string|null The encryption method that should be used, if any (ssl or tls)
     */
    public $encryptionMethod;

    /**
     * @var string The timeout duration (in seconds)
     */
    public $timeout = 10;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->password) {
            $this->password = StringHelper::decdec($this->password);
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'host' => Craft::t('app', 'Host Name'),
            'port' => Craft::t('app', 'Port'),
            'useAuthentication' => Craft::t('app', 'Use authentication'),
            'username' => Craft::t('app', 'Username'),
            'password' => Craft::t('app', 'Password'),
            'encryptionMethod' => Craft::t('app', 'Encryption Method'),
            'timeout' => Craft::t('app', 'Timeout'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['host', 'port', 'timeout'], 'required'],
            [
                ['username', 'password'],
                'required',
                'when' => function($model) {
                    /** @var self $model */
                    return (bool)$model->useAuthentication;
                }
            ],
            [['encryptionMethod'], 'in', 'range' => ['tls', 'ssl']],
            [['timeout'], 'number', 'integerOnly' => true],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('_components/mailertransportadapters/Smtp/settings', [
            'adapter' => $this
        ]);
    }

    /**
     * @inheritdoc
     */
    public function defineTransport()
    {
        $config = [
            'class' => \Swift_SmtpTransport::class,
            'host' => $this->host,
            'port' => $this->port,
            'timeout' => $this->timeout,
        ];

        if ($this->useAuthentication) {
            $config['username'] = $this->username;
            $config['password'] = $this->password;
        }

        if ($this->encryptionMethod) {
            $config['encryption'] = $this->encryptionMethod;
        }

        return $config;
    }
}
