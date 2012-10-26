<?php

return array (
	/* BLOCKSPRO ONLY */
	'verify_email_heading' => 'When someone creates an account:',
	'verify_email_subject' => 'Verify your email address',
	'verify_email_body' => "Hey {{user.friendlyName}},\n\n" .
		"Thanks for creating an account with {{siteName}}! Before we activate your account, please verify your email address by clicking on this link:\n\n" .
		"{{link}}\n\n" .
		"If you weren't expecting this email, just ignore it.",

	'verify_new_email_heading' => 'When someone changes their email address:',
	'verify_new_email_subject' => 'Verify your new email address',
	'verify_new_email_body' => "Hey {{user.friendlyName}},\n\n" .
		"Please verify your new email address by clicking on this link:\n\n" .
		"{{link}}\n\n" .
		"If you weren't expecting this email, just ignore it.",

	'forgot_password_heading' => 'When someone forgets their password:',
	/* end BLOCKSPRO ONLY */
	'forgot_password_subject' => 'Reset your password',
	'forgot_password_body' => "Hey {{user.friendlyName}},\n\n" .
		"To reset your {{siteName}} password, click on this link:\n\n" .
		"{{link}}\n\n" .
		"If you weren't expecting this email, just ignore it.",
	'test_email_subject' => 'This is a test email from Blocks',
	'test_email_body' => "Hey {{user.friendlyName}},\n\n".
		"Congratulations!\n\nBlocks was successfully able to send an email.\n\n".
		"Here are the settings you used:\n\n".
		"{% for key, setting in settings %}".
		"{{ key }}:  {{ setting }}\n".
		"{% endfor %}",
);
