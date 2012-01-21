<?php

/**
 * bEmailSettingsForm class.
 * It is used by the 'saveEmail' action of 'bSettingsController'.
 */
class bEmailSettingsForm extends CFormModel
{
	public $emailType;
	public $hostName;
	public $port;
	public $smtpAuth;
	public $userName;
	public $password;
	public $smtpKeepAlive;
	public $smtpSecureTransport;
	public $smtpSecureTransportType;

	/**
	 * Declares the validation rules.
	 * @return array of validation rules.
	 */
	public function rules()
	{
		$rules[] = array('emailType', 'required');

		switch ($this->emailType)
		{
			case bEmailerType::GmailSmtp:
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

				$rules[] = array('port, hostName', 'required');
				break;
			}

			case bEmailerType::Pop:
			{
				$rules[] = array('port, hostName, userName, password', 'required');
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
