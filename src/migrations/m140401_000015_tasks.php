<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140401_000015_tasks extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if (!craft()->db->tableExists('tasks'))
		{
			// Create the craft_tasks table
			craft()->db->createCommand()->createTable('tasks', array(
				'root'        => array('maxLength' => 11, 'decimals' => 0, 'column' => 'integer', 'unsigned' => true),
				'lft'         => array('maxLength' => 11, 'decimals' => 0, 'column' => 'integer', 'unsigned' => true, 'null' => false),
				'rgt'         => array('maxLength' => 11, 'decimals' => 0, 'column' => 'integer', 'unsigned' => true, 'null' => false),
				'level'       => array('maxLength' => 6, 'decimals' => 0, 'column' => 'smallint', 'unsigned' => true, 'null' => false),
				'currentStep' => array('maxLength' => 11, 'decimals' => 0, 'column' => 'integer', 'unsigned' => true),
				'totalSteps'  => array('maxLength' => 11, 'decimals' => 0, 'column' => 'integer', 'unsigned' => true),
				'status'      => array('values' => array('pending', 'error', 'running'), 'column' => 'enum'),
				'type'        => array('maxLength' => 150, 'column' => 'char', 'required' => true),
				'description' => array('maxLength' => 255, 'column' => 'varchar'),
				'settings'    => array('column' => 'text', 'required' => true),
			), null, true);

			// Add indexes to craft_tasks
			craft()->db->createCommand()->createIndex('tasks', 'root', false);
			craft()->db->createCommand()->createIndex('tasks', 'lft', false);
			craft()->db->createCommand()->createIndex('tasks', 'rgt', false);
			craft()->db->createCommand()->createIndex('tasks', 'level', false);
		}

		return true;
	}
}
