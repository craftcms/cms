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
	 * @param $properties
	 * @param string $scenario
	 */
	function __construct($properties = null, $scenario = '')
	{
		parent::__construct($scenario);

		if ($properties !== null)
		{
			$this->protocol = isset($properties['protocol']) ? $properties['protocol'] : null;
			$this->host = isset($properties['host']) ? $properties['host'] : null;
			$this->password = isset($properties['password']) ? $properties['password'] : null;
			$this->port = isset($properties['port']) ? $properties['port'] : null;
			$this->smtpAuth = isset($properties['smtpAuth']) ? $properties['smtpAuth'] : null;
			$this->smtpKeepAlive = isset($properties['smtpKeepAlive']) ? $properties['smtpKeepAlive'] : null;
			$this->smtpSecureTransportType = isset($properties['smtpSecureTransportType']) ? $properties['smtpSecureTransportType'] : 'none';
			$this->username = isset($properties['username']) ? $properties['username'] : null;
			$this->timeout = isset($properties['timeout']) ? $properties['timeout'] : null;
			$this->emailAddress = isset($properties['emailAddress']) ? $properties['emailAddress'] : null;
			$this->senderName = isset($properties['senderName']) ? $properties['senderName'] : null;
		}
	}

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
