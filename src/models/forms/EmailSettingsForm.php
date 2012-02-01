<?php
namespace Blocks;

/**
 * EmailSettingsForm class.
 * It is used by the 'saveEmail' action of 'settingsController'.
 */
class EmailSettingsForm extends \CFormModel
{
	public $emailerType;
	public $host;
	public $port;
	public $smtpAuth;
	public $userName;
	public $password;
	public $smtpKeepAlive;
	public $smtpSecureTransport;
	public $smtpSecureTransportType;
	public $timeout;
	public $fromEmail;
	public $fromName;

	/**
	 * @param $properties
	 * @param string $scenario
	 */
	function __construct($properties = null, $scenario = '')
	{
		parent::__construct($scenario);

		if ($properties !== null)
		{
			$this->emailerType = isset($properties['emailerType']) ? $properties['emailerType'] : null;
			$this->host = isset($properties['host']) ? $properties['host'] : null;
			$this->password = isset($properties['password']) ? $properties['password'] : null;
			$this->port = isset($properties['port']) ? $properties['port'] : null;
			$this->smtpAuth = isset($properties['smtpAuth']) ? $properties['smtpAuth'] : null;
			$this->smtpKeepAlive = isset($properties['smtpKeepAlive']) ? $properties['smtpKeepAlive'] : null;
			$this->smtpSecureTransport = isset($properties['smtpSecureTransport']) ? $properties['smtpSecureTransport'] : null;
			$this->smtpSecureTransportType = isset($properties['smtpSecureTransportType']) ? $properties['smtpSecureTransportType'] : null;
			$this->userName = isset($properties['userName']) ? $properties['userName'] : null;
			$this->timeout = isset($properties['timeout']) ? $properties['timeout'] : null;
			$this->fromEmail = isset($properties['fromEmail']) ? $properties['fromEmail'] : null;
			$this->fromName = isset($properties['fromName']) ? $properties['fromName'] : null;
		}
	}

	/**
	 * Declares the validation rules.
	 * @return array of validation rules.
	 */
	public function rules()
	{
		$rules[] = array('emailerType, fromEmail, fromName', 'required');

		switch ($this->emailerType)
		{
			case EmailerType::Smtp:
			{
				if ($this->smtpAuth)
				{
					$rules[] = array('userName, password', 'required');
				}

				if ($this->smtpSecureTransport)
				{
					$rules[] = array('smtpSecureTransportType', 'required');
				}

				$rules[] = array('port, host, timeout', 'required');
				break;
			}

			case EmailerType::GmailSmtp:
			{
				$rules[] = array('userName, password, timeout', 'required');
				$rules[] = array('userName', 'email');
				break;
			}

			case EmailerType::Pop:
			{
				$rules[] = array('port, host, userName, password, timeout', 'required');
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
