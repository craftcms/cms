<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140204_000011_user_email_verification_settings extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if (craft()->hasPackage(CraftPackage::Users))
		{
			$settings = craft()->systemSettings->getSettings('users');

			if (!empty($settings['allowPublicRegistration']) && !isset($settings['requireEmailVerification']))
			{
				$settings['requireEmailVerification'] = 1;

				if (craft()->systemSettings->saveSettings('users', $settings))
				{
					Craft::log('Successfully set requireEmailVerification to true in user settings.', LogLevel::Info, true);
				}
				else
				{
					Craft::log('There was a problem setting requireEmailVerification to true in user settings.', LogLevel::Error, true);
				}
			}
			else
			{
				Craft::log('Users package is installed, but allowPublicRegistration is off. Nothing to do.', LogLevel::Info, true);
			}
		}
		else
		{
			Craft::log('Users package is not installed. Nothing to do.', LogLevel::Info, true);
		}

		return true;
	}
}
