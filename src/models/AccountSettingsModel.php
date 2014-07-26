<?php
namespace Craft;

/**
 * Validates the required User attributes for the installer.
 *
 * @package craft.app.models
 */
class AccountSettingsModel extends BaseModel
{
	/**
	 * @return array
	 */
	protected function defineAttributes()
	{
		$requireUsername = !craft()->config->get('useEmailAsUsername');

		return array(
			'username' => array(AttributeType::String, 'maxLength' => 100, 'required' => $requireUsername),
			'email'    => array(AttributeType::Email, 'required' => true),
			'password' => array(AttributeType::String, 'minLength' => 6, 'required' => true)
		);
	}

	/**
	 * @param null $attributes
	 * @param bool $clearErrors
	 * @return bool|void
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
}
