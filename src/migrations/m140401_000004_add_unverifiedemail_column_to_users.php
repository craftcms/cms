<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140401_000004_add_unverifiedemail_column_to_users extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if (craft()->db->tableExists('users'))
		{
			if (!craft()->db->columnExists('users', 'unverifiedEmail'))
			{
				$this->addColumnAfter('users', 'unverifiedEmail', array('column' => ColumnType::Varchar, 'maxLength' => 255), 'verificationCodeIssuedDate');
				Craft::log('Successfully added the `unverifiedEmail` column to the `users` table.', LogLevel::Info, true);
			}
			else
			{
				Craft::log('Tried to add the `unverifiedEmail` column to the users table, but it already exists.', LogLevel::Warning, true);
			}
		}
		else
		{
			Craft::log('The users table doesn’t exist. Something is very wrong.', LogLevel::Error, true);
		}

		return true;
	}
}
