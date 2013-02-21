<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130218_015336_session_shuffle extends BaseMigration
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
			if (($tokenColumn = $usersTable->getColumn('authSessionToken')) !== null)
			{
				$columns = array(
				    'userId' => array(AttributeType::Number, 'column' => ColumnType::Int, 'required' => true),
				    'token' => array(AttributeType::String, 'maxLength' => 100, 'column' => ColumnType::Char, 'required' => true),
				);

				$sessionsTable = $this->dbConnection->schema->getTable('{{sessions}}');

				if (!$sessionsTable)
				{
					// Create the new sessions table.
					$this->createTable('{{sessions}}', $columns, null, true, true);
					$this->addForeignKey('sessions', 'userId', 'users', 'id', 'CASCADE');

					// Select all users that have existing session tokens.
					$existingRows = $this->dbConnection->createCommand('SELECT `id`, `authSessionToken` FROM `'.$usersTable->name.'` WHERE `authSessionToken` IS NOT NULL')->queryAll();

					$path = blx()->path->getLibPath().'PasswordHash.php';
					require_once $path;

					// Copy them into the new table.
					foreach ($existingRows as $existingRow)
					{
						$hashedToken = blx()->security->hashString($existingRow['authSessionToken']);
						Blocks::log('Inserting userId: '.$existingRow['id'].' and token: '.$hashedToken['hash'].' into sessions table.');
						$this->insert('{{sessions}}', array('userId' => $existingRow['id'], 'token' => $hashedToken['hash']));
					}
				}
				else
				{
					Blocks::log('The `sessions` table already exists in the database.');
				}

				// Remove the old authSessionToken column in users table.
				$this->dropColumn('{{users}}', 'authSessionToken');

				// Change the length of the verificationCode column.
				$this->alterColumn('{{users}}', 'verificationCode', array(AttributeType::String, 'maxLength' => 100, 'column' => ColumnType::Char));

				// Create additional users indexes.
				$this->createIndex('{{users}}', 'uid');
				$this->createIndex('{{users}}', 'verificationCode');

				// Create additional sessions indexes.
				$this->createIndex('{{sessions}}', 'uid');
				$this->createIndex('{{sessions}}', 'token');
				$this->createIndex('{{sessions}}', 'dateUpdated');
			}
			else
			{
				Blocks::log('The `authSessionToken` column does not exist in the `users` table.', \CLogger::LEVEL_WARNING);
			}
		}
		else
		{
			Blocks::log('The `users` table is missing. No idea what is going on here.', \CLogger::LEVEL_ERROR);
		}
	}
}
