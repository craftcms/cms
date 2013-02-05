<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m121019_144608_move_user_name_cols extends DbMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Create firstName and lastName columns in the users table
		$type = array('type' => AttributeType::String, 'maxLength' => 100);
		blx()->db->createCommand()->addColumnAfter('users', 'firstName', $type, 'username');
		blx()->db->createCommand()->addColumnAfter('users', 'lastName', $type, 'firstName');

		// Migrate user names over to the users table
		$rows = blx()->db->createCommand()
			->select('userId, firstName, lastName')
			->from('userprofiles')
			->where('firstName is not null or lastName is not null')
			->queryAll();

		foreach ($rows as $row)
		{
			blx()->db->createCommand()->update('users',
				array('firstName' => $row['firstName'], 'lastName' => $row['lastName']),
				array('id' => $row['userId'])
			);
		}

		// Drop the user name columns from the user profiles table
		blx()->db->createCommand()->dropColumn('userprofiles', 'firstName');
		blx()->db->createCommand()->dropColumn('userprofiles', 'lastName');

		return true;
	}
}
