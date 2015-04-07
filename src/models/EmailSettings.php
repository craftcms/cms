<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\base\Model;
use craft\app\enums\EmailerType;

/**
 * EmailSettings Model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EmailSettings extends Model
{
	// Properties
	// =========================================================================

	/**
	 * @var string Protocol
	 */
	public $protocol;

	/**
	 * @var string Host
	 */
	public $host;

	/**
	 * @var string Port
	 */
	public $port;

	/**
	 * @var string Smtp auth
	 */
	public $smtpAuth;

	/**
	 * @var string Username
	 */
	public $username;

	/**
	 * @var string Password
	 */
	public $password;

	/**
	 * @var boolean Smtp keep alive
	 */
	public $smtpKeepAlive = false;

	/**
	 * @var string Smtp secure transport type
	 */
	public $smtpSecureTransportType;

	/**
	 * @var string Timeout
	 */
	public $timeout;

	/**
	 * @var string Email address
	 */
	public $emailAddress;

	/**
	 * @var string Sender name
	 */
	public $senderName;

	/**
	 * @var string Test email address
	 */
	public $testEmailAddress;

	/**
	 * @var string Template
	 */
	public $template;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		$rules = [
			[['protocol', 'emailAddress', 'senderName'], 'required'],
		];

		switch ($this->protocol)
		{
			case EmailerType::Smtp:
			{
				if ($this->smtpAuth)
				{
					$rules[] = [['username', 'password'], 'required'];
				}

				$rules[] = [['port', 'host', 'timeout'], 'required'];
				break;
			}

			case EmailerType::Gmail:
			{
				$rules[] = [['username', 'password', 'timeout'], 'required'];
				$rules[] = ['username', 'email'];
				break;
			}

			case EmailerType::Pop:
			{
				$rules[] = [['port', 'host', 'username', 'password', 'timeout'], 'required'];
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
