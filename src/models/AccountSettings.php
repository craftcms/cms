<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\Model;
use craft\app\enums\AttributeType;

/**
 * Validates the required User attributes for the installer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AccountSettings extends Model
{
	// Public Methods
	// =========================================================================

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

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc Model::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		$requireUsername = !Craft::$app->config->get('useEmailAsUsername');

		return [
			'username' => [AttributeType::String, 'maxLength' => 100, 'required' => $requireUsername],
			'email'    => [AttributeType::Email, 'required' => true],
			'password' => [AttributeType::String, 'minLength' => 6, 'required' => true]
		];
	}
}
