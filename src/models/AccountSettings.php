<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\Model;

/**
 * Validates the required User attributes for the installer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AccountSettings extends Model
{
	// Properties
	// =========================================================================

	/**
	 * @var string Username
	 */
	public $username;

	/**
	 * @var string Email
	 */
	public $email;

	/**
	 * @var string Password
	 */
	public $password;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['username', 'email', 'password'], 'required'],
			[['email'], 'email'],
			[['email'], 'string', 'min' => 5],
			[['password'], 'string', 'min' => 6],
			[['username'], 'string', 'max' => 100],
			[['email'], 'string', 'max' => 255],
			[['username', 'email', 'password'], 'safe', 'on' => 'search'],
		];
	}

	/**
	 * Validates all of the attributes for the current Model. Any attributes that fail validation will additionally get
	 * logged to the `craft/storage/logs` folder as a warning.
	 *
	 * In addition, we check that the username does not have any whitespace in it.
	 *
	 * @param null $attributes
	 * @param bool $clearErrors
	 *
	 * @return bool|null
	 */
	public function validate($attributes = null, $clearErrors = true)
	{
		// Don't allow whitespace in the username.
		if (preg_match('/\s+/', $this->username))
		{
			$this->addError('username', Craft::t('app', 'Spaces are not allowed in the username.'));
		}

		return parent::validate($attributes, false);
	}
}
