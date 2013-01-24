<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m121126_225547_cascade_deletes extends DbMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Find all record classes
		$records = blx()->install->findInstallableRecords();

		// Add any section content records to the mix
		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$criteria = new SectionCriteria();
			$sections = blx()->sections->findSections($criteria);

			foreach ($sections as $section)
			{
				$records[] = new SectionContentRecord($section);
			}
		}

		foreach ($records as $key => $record)
		{
			if (!$record->tableExists())
			{
				unset($records[$key]);
			}
		}

		// Drop the foreign keys
		foreach ($records as $record)
		{
			$table = $record->getTableName();

			if ($table !== 'migrations')
			{
				foreach ($record->getBelongsToRelations() as $name => $config)
				{
					$otherRecord = new $config[1];

					if ($otherRecord->tableExists())
					{
						$fkName = "{$table}_{$name}_fk";
						$this->_dropForeignKey($fkName, $table);
					}
				}
			}
		}

		// Add them back (this time with CASCADE rules on FK deletion)
		foreach ($records as $record)
		{
			$table = $record->getTableName();

			if ($table !== 'migrations')
			{
				foreach ($record->getBelongsToRelations() as $name => $config)
				{
					$otherRecord = new $config[1];
					$otherTable = $otherRecord->getTableName();
					$fkName = "{$table}_{$name}_fk";

					if (isset($config['onDelete']))
					{
						$onDelete = $config['onDelete'];
					}
					else
					{
						if (empty($config['required']))
						{
							$onDelete = BaseRecord::SET_NULL;
						}
						else
						{
							$onDelete = null;
						}
					}

					$this->_addForeignKey($fkName, $table, $config[2], $otherTable, 'id', $onDelete);
				}
			}
		}

		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			// Don't forget the usergroups_users table
			$this->_dropForeignKey('usergroups_users_group_fk', 'usergroups_users');
			$this->_dropForeignKey('usergroups_users_user_fk', 'usergroups_users');
			$this->_addForeignKey('usergroups_users_group_fk', 'usergroups_users', 'groupId', 'usergroups', 'id', 'CASCADE');
			$this->_addForeignKey('usergroups_users_user_fk', 'usergroups_users', 'userId', 'users', 'id', 'CASCADE');
		}

		// Drop the old pluginId FK from blx_widgets while we're at it
		$this->_dropForeignKey('widgets_plugin_fk', 'widgets');
		blx()->db->createCommand()->dropColumn('widgets', 'pluginId');

		return true;
	}

	/**
	 * @param      $name
	 * @param      $table
	 * @param      $columns
	 * @param      $refTable
	 * @param      $refColumns
	 * @param null $delete
	 * @param null $update
	 * @return int
	 */
	private function _addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete = null, $update = null)
	{
		$name = md5(blx()->db->tablePrefix.$name);
		$table = DbHelper::addTablePrefix($table);
		$refTable = DbHelper::addTablePrefix($refTable);
		return blx()->db->createCommand()->setText(blx()->db->getSchema()->addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete, $update))->execute();
	}

	/**
	 * @param $name
	 * @param $table
	 * @return int
	 */
	private function _dropForeignKey($name, $table)
	{
		$name = md5(blx()->db->tablePrefix.$name);
		$table = DbHelper::addTablePrefix($table);
		return blx()->db->createCommand()->setText(blx()->db->getSchema()->dropForeignKey($name, $table))->execute();
	}
}
