<?php
namespace Craft;

/**
 * EmailSettingsModel class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.models
 * @since     1.0
 */
class EmailSettingsModel extends BaseModel
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::rules()
	 *
	 * @return array
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

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'protocol'                => AttributeType::String,
			'host'                    => AttributeType::String,
			'port'                    => AttributeType::String,
			'smtpAuth'                => AttributeType::String,
			'username'                => AttributeType::String,
			'password'                => AttributeType::String,
			'smtpKeepAlive'           => AttributeType::Bool,
			'smtpSecureTransportType' => AttributeType::String,
			'timeout'                 => AttributeType::String,
			'emailAddress'            => AttributeType::Email,
			'senderName'              => AttributeType::String,
			'testEmailAddress'        => AttributeType::Email,
			'template'                => AttributeType::String,
		);
	}
}
