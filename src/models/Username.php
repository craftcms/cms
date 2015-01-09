<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\enums\AttributeType;

/**
 * Username model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Username extends BaseModel
{
	// Public Methods
	// =========================================================================

	/**
	 * Validates all of the attributes for the current Model. Any attributes that fail validation will additionally get
	 * logged to the `craft/storage/runtime/logs` folder with a level of LogLevel::Warning.
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
			$this->addError('username', Craft::t('Spaces are not allowed in the username.'));
		}

		return parent::validate($attributes, false);
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
		$requireUsername = !Craft::$app->config->get('useEmailAsUsername');

		return [
			'username' => [AttributeType::String, 'maxLength' => 100, 'required' => $requireUsername],
		];
	}
}
