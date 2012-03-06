<?php
namespace Blocks;

/**
 *
 */
class ChangePasswordForm extends \CFormModel
{
	public $password;
	public $confirmPassword;
	public $currentPassword;

/**
 * @return array
 */
	public function rules()
	{
		return array(
			array('password, confirmPassword, currentPassword', 'required'),
			array('password', 'compare', 'compareAttribute' => 'confirmPassword'),
			array('password', 'length', 'min' => b()->security->minimumPasswordLength)
		);
	}
}
