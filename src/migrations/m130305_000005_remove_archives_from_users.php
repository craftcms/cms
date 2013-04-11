<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130305_000005_remove_archives_from_users extends BaseMigration
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
			// Delete any existing archived users.
			$this->delete('users', 'archivedUsername IS NOT NULL OR archivedEmail IS NOT NULL');

			// Delete the archivedUsername
			if (($archivedUsernameColumn = $usersTable->getColumn('archivedUsername')) !== null)
			{
				$this->dropColumn('users', 'archivedUsername');
				Craft::log('Dropped the `archivedUsername` column from the `users` table.');
			}
			else
			{
				Craft::log('Could not find a `archivedUsername` column in the `users` table.', LogLevel::Warning);
			}

			// Delete archivedEmail
			if (($archivedEmailColumn = $usersTable->getColumn('archivedEmail')) !== null)
			{
				$this->dropColumn('users', 'archivedEmail');
				Craft::log('Dropped the `archivedEmail` column from the `users` table.');
			}
			else
			{
				Craft::log('Could not find a `archivedEmail` column in the `users` table.', LogLevel::Warning);
			}
		}
		else
		{
			Craft::log('Could not find an `users` table. Wut?', LogLevel::Error);
		}
	}
}
