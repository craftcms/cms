<?php

return array (
	'verify_email_heading' => 'When someone creates an account:',
	'verify_email_default_subject' => 'Verify your email address',
	'verify_email_default_body' => "Hey {:username},\n\n" .
		"Thanks for creating an account with {:sitename}! Before we activate your account, please verify your email address by clicking on this link:\n\n" .
		"{:link}\n\n" .
		"If you weren’t expecting this email, just ignore it.",

	'verify_new_email_heading' => 'When someone changes their email address:',
	'verify_new_email_default_subject' => 'Verify your new email address',
	'verify_new_email_default_body' => "Hey {:username},\n\n" .
		"Please verify your new email address by clicking on this link:\n\n" .
		"{:link}\n\n" .
		"If you weren’t expecting this email, just ignore it.",

	'forgot_password_heading' => 'When someone forgets their password:',
	'forgot_password_default_subject' => 'Reset your password',
	'forgot_password_default_body' => "Hey {:username},\n\n" .
		"To reset your Pixel & Tonic password, click on this link:" .
		"{:link}\n\n" .
		"If you weren’t expecting this email, just ignore it."
);
