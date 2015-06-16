<?php
/**
 * @link      http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\Model;

/**
 * Represents the transport settings that the Mailer instance should be initialized with
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class MailerTransportSettings extends Model
{
    // Properties
    // =========================================================================

    /**
     * @param string The transport type that should be used
     */
    public $protocol = 'php';

    /**
     * @param boolean Whether SMTP authentication should be used, if the transport type is SMTP
     */
    public $smtpAuth;

    /**
     * @param string The username that should be sent if SMTP authentication is enabled
     */
    public $username;

    /**
     * @param string The password that should be sent if SMTP authentication is enabled
     */
    public $password;

    /**
     * @param string The encryption method that should be used when sending emails via SMTP
     */
    public $smtpSecureTransportType;

    /**
     * @param string The host that should be used when sending emails via SMTP
     */
    public $host;

    /**
     * @param string The port that should be used when sending emails via SMTP
     */
    public $port;

    /**
     * @param integer The timeout duration (in seconds)
     */
    public $timeout = 10;

    /**
     * @param string The template that emails should be sent with
     */
    public $template;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['protocol'], 'in', 'range' => ['php', 'sendmail', 'smtp', 'gmail']],
            [['username', 'password'], 'required', 'when' => function($model) {
                /** @var self $model */
                return $model->protocol == 'smtp' && $model->smtpAuth == true;
            }],
            [['encryptionMethod'], 'in', 'range' => ['none', 'tls', 'ssl'], 'when' => function($model) {
                /** @var self $model */
                return $model->protocol == 'smtp';
            }],
            [['host', 'port'], 'required', 'when' => function($model) {
                /** @var self $model */
                return $model->protocol == 'smtp';
            }],
            [['timeout'], 'number', 'integerOnly' => true],
        ];
    }

    /**
     * Returns a new Swiftmailer transport instance based on these settings.
     *
     * @return \Swift_Transport|false
     */
    public function createTransport()
    {
        if (!$this->validate()) {
            return false;
        }

        switch ($this->protocol) {
            case 'gmail':
            case 'smtp': {
                $transport = new \Swift_SmtpTransport($this->host, $this->port);

                if ($this->smtpAuth) {
                    $transport->setUsername($this->username);
                    $transport->setPassword($this->password);
                }

                if ($this->smtpSecureTransportType && $this->smtpSecureTransportType != 'none') {
                    $transport->setEncryption($this->smtpSecureTransportType);
                }

                if ($this->timeout) {
                    $transport->setTimeout($this->timeout);
                }

                break;
            }
            case 'sendmail': {
                $transport = new \Swift_SendmailTransport();
                break;
            }
            default: {
                $transport = new \Swift_MailTransport();
            }
        }

        return $transport;
    }

}
