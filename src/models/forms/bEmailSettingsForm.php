<?php

/**
 * bEmailSettingsForm class.
 * It is used by the 'saveEmail' action of 'bSettingsController'.
 */
class bEmailSettingsForm extends CFormModel
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
		}
	}

	/**
	 * Declares the validation rules.
	 * @return array of validation rules.
	 */
	public function rules()
	{
		$rules[] = array('emailerType', 'required');

		switch ($this->emailerType)
		{
			case bEmailerType::Smtp:
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

			case bEmailerType::GmailSmtp:
			{
				$rules[] = array('userName, password, timeout', 'required');
				$rules[] = array('userName', 'email');
				break;
			}

			case bEmailerType::Pop:
			{
				$rules[] = array('port, host, userName, password, timeout', 'required');
				break;
			}

			case bEmailerType::PhpMail:
			case bEmailerType::Sendmail:
			{
				break;
			}
		}

		return $rules;
	}
}
