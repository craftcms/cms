<?php
namespace Blocks;

/**
 *
 */
class ForgotPasswordForm extends \CFormModel
{
	public $loginName;

	/**
	 * @return array
	 */
	public function rules()
	{
		return array(
			array('loginName', 'required'),
		);
	}
}
