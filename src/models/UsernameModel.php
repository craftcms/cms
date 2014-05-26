<?php
namespace Craft;

/**
 * Username model
 */
class UsernameModel extends BaseModel
{
	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		$requireUsername = !craft()->config->get('useEmailAsUsername');

		return array(
			'username' => array(AttributeType::String, 'maxLength' => 100, 'required' => $requireUsername),
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
