<?php
/**
 * Craft by Pixel & Tonic
 *
 * @package   Craft
 * @author    Pixel & Tonic, Inc.
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 */
return array (
	'account_activation_heading' => 'When someone creates an account:',
	'account_activation_subject' => 'Activate your account',
	'account_activation_body' => "Hey {{user.friendlyName}},\n\n" .
		"Thanks for creating an account with {{siteName}}! To activate your account, click the following link:\n\n" .
		"{{link}}\n\n" .
		"If you were not expecting this email, just ignore it.",
	'verify_new_email_heading' => 'When someone changes their email address:',
	'verify_new_email_subject' => 'Verify your new email address',
	'verify_new_email_body' => "Hey {{user.friendlyName}},\n\n" .
		"Please verify your new email address by clicking on this link:\n\n" .
		"{{link}}\n\n" .
		"If you were not expecting this email, just ignore it.",
	'forgot_password_heading' => 'When someone forgets their password:',
	'forgot_password_subject' => 'Reset your password',
	'forgot_password_body' => "Hey {{user.friendlyName}},\n\n" .
		"To reset your {{siteName}} password, click on this link:\n\n" .
		"{{link}}\n\n" .
		"If you were not expecting this email, just ignore it.",
	'test_email_heading' => 'When you are testing your email settings:',
	'test_email_subject' => 'This is a test email from Craft',
	'test_email_body' => "Hey {{user.friendlyName}},\n\n".
		"Congratulations! Craft was successfully able to send an email.\n\n".
		"Here are the settings you used:\n\n".
		"{% for key, setting in settings %}".
		"{{ key }}:  {{ setting }}\n\n".
		"{% endfor %}",
);
