<?php
namespace Craft;

/**
 * EmailSettingsModel class.
 * It is used by the 'saveEmail' action of 'settingsController'.
 */
class EmailSettingsModel extends \CFormModel
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
	public $testEmailAddress;

	/**
	 * Declares the validation rules.
	 *
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

			case EmailerType::Gmail:
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

			case EmailerType::Php:
			case EmailerType::Sendmail:
			{
				break;
			}
		}

		return $rules;
	}
}
