<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130416_000002_clean_email_format extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$usersTable = $this->dbConnection->schema->getTable('{{users}}');

		if ($usersTable)
		{
			if (($emailFormatColumn = $usersTable->getColumn('emailFormat')) !== null)
			{
				Craft::log('Dropping the `emailFormat` column from `users`', LogLevel::Info, true);
				$this->dropColumn('users', 'emailFormat');
				Craft::log('Dropped the `emailFormat` column from `users`', LogLevel::Info, true);

			}
			else
			{
				Craft::log('Tried to drop the `emailFormat` column from `users`, but the column is missing.', LogLevel::Warning);
			}
		}
		else
		{
			Craft::log('Tried to drop the `emailFormat` column from `users`, but the table does not exist!', LogLevel::Error);
		}

		$emailMessagesTable = $this->dbConnection->schema->getTable('{{emailmessages}}');

		if ($emailMessagesTable)
		{
			if (($htmlBodyColumn = $emailMessagesTable->getColumn('htmlBody')) !== null)
			{
				Craft::log('Dropping the `htmlBody` column from `emailmessages`.', LogLevel::Info, true);
				$this->dropColumn('emailmessages', 'htmlBody');
				Craft::log('Dropped the `htmlBody` column from `emailMessages`', LogLevel::Info, true);
			}
			else
			{
				Craft::log('Tried to drop the `htmlBody` column from `emailmessages`, but the column is missing.', LogLevel::Warning);
			}
		}
		else
		{
			Craft::log('Tried to drop the `emailFormat` column from `emailmessages`, but the table does not exist!', LogLevel::Error);
		}

		return true;
	}
}
