<?php
namespace Blocks;

/**
 * EmailSettingsForm class.
 * It is used by the 'saveEmail' action of 'settingsController'.
 */
class EmailSettingsForm extends \CFormModel
{
	public $protocol;
	public $host;
	public $port;
	public $smtpAuth;
	public $username;
	public $password;
	public $smtpKeepAlive;
	public $smtpSecureTransportType;
	public $timeout;
	public $emailAddress;
	public $senderName;



	/**
	 * Declares the validation rules.
	 * @return array of validation rules.
	 */
	public function rules()
	{
		$rules[] = array('protocol, emailAddress, senderName', 'required');

		switch ($this->protocol)
		{
			case EmailerType::Smtp:
			{
				if ($this->smtpAuth)
				{
					$rules[] = array('username, password', 'required');
				}

				$rules[] = array('port, host, timeout', 'required');
				break;
			}

			case EmailerType::GmailSmtp:
			{
				$rules[] = array('username, password, timeout', 'required');
				$rules[] = array('username', 'email');
				break;
			}

			case EmailerType::Pop:
			{
				$rules[] = array('port, host, username, password, timeout', 'required');
				break;
			}

			case EmailerType::PhpMail:
			case EmailerType::Sendmail:
			{
				break;
			}
		}

		return $rules;
	}
}
