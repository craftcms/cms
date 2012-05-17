<?php
namespace Blocks;

/**
 * Forgot Password form
 */
class ForgotPasswordForm extends BaseForm
{
	protected $attributes = array(
		'username' => array('required' => true)
	);
}
