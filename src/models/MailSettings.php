<?php
/**
 * @link      http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\Model;
use craft\app\mail\Mailer;

/**
 * MailSettings Model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class MailSettings extends Model
{
    // Constants
    // =========================================================================

    const PROTOCOL_PHP = 'php';
    const PROTOCOL_SENDMAIL = 'sendmail';
    const PROTOCOL_SMTP = 'smtp';
    const PROTOCOL_GMAIL = 'gmail';

    // Properties
    // =========================================================================

    /**
     * @var string The default email address that emails should be sent from
     */
    public $fromEmail;

    /**
     * @var string The default name that emails should be sent from
     */
    public $fromName;

    /**
     * @var string The template that emails should be sent with
     */
    public $template;

    /**
     * @var string The transport type that should be used
     */
    public $protocol;

    /**
     * @var string The host that should be used when sending emails via SMTP
     */
    public $host;

    /**
     * @var string The port that should be used when sending emails via SMTP
     */
    public $port;

    /**
     * @var string Smtp auth
     */
    public $useAuthentication;

    /**
     * @var string Username
     */
    public $username;

    /**
     * @var string Password
     */
    public $password;

    /**
     * @var string Smtp secure transport type
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
    public function attributeLabels()
    {
        return [
            'fromEmail' => Craft::t('app', 'System Email Address'),
            'fromName' => Craft::t('app', 'Sender Name'),
            'template' => Craft::t('app', 'HTML Email Template'),
            'protocol' => Craft::t('app', 'Protocol'),
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
        $rules = [
            [['protocol', 'fromEmail', 'fromName'], 'required'],
            [['fromEmail'], 'email'],
        ];

        switch ($this->protocol) {
            case self::PROTOCOL_SMTP: {
                if ($this->useAuthentication) {
                    $rules[] = [['username', 'password'], 'required'];
                }

                $rules[] = [['port', 'host', 'timeout'], 'required'];
                break;
            }

            case self::PROTOCOL_GMAIL: {
                $rules[] = [['username', 'password', 'timeout'], 'required'];
                $rules[] = ['username', 'email'];
                break;
            }
        }

        return $rules;
    }

    /**
     * Returns a new Mailer instance based on these settings.
     *
     * @return Mailer|false
     */
    public function createMailer()
    {
        switch ($this->protocol) {
            case self::PROTOCOL_SMTP: {
                $transport = new \Swift_SmtpTransport($this->host, $this->port);

                if ($this->useAuthentication) {
                    $transport->setUsername($this->username);
                    $transport->setPassword($this->password);
                }

                if ($this->encryptionMethod && $this->encryptionMethod != 'none') {
                    $transport->setEncryption($this->encryptionMethod);
                }

                if ($this->timeout) {
                    $transport->setTimeout($this->timeout);
                }

                break;
            }
            case self::PROTOCOL_GMAIL: {
                $transport = new \Swift_SmtpTransport('smtp.gmail.com', 465);
                $transport->setEncryption('ssl');

                $transport->setUsername($this->username);
                $transport->setPassword($this->password);
                $settings['timeout'] = $this->timeout;

                break;
            }
            case self::PROTOCOL_SENDMAIL: {
                $transport = new \Swift_SendmailTransport();
                break;
            }
            default: {
                $transport = new \Swift_MailTransport();
            }
        }

        return new Mailer([
            'template' => $this->template,
            'from' => [$this->fromEmail => $this->fromName],
            'transport' => $transport
        ]);
    }
}
