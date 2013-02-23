<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130221_000000_innodb_check extends BaseMigration
{
	/**
	 * Check to see if MySQL has InnoDB support enabled.  If it does, check all of the Blocks tables for this installation
	 * to make sure they are using InnoDB.  If not in both cases, throw and exception.
	 *
	 * @throws Exception
	 * @return bool
	 */
	public function safeUp()
	{
		if (!blx()->db->getSchema()->isInnoDbEnabled())
		{
			throw new Exception('Blocks requires the MySQL InnoDB storage engine and it is not enabled for this MySQL installation.');
		}
		else
		{
			$tables = blx()->db->getSchema()->getTableNames();

			$badTables = array();
			foreach ($tables as $table)
			{
				$sql = blx()->db->createCommand()->setText('SHOW CREATE TABLE `'.$table.'`')->queryRow();

				if (isset($sql['Create Table']))
				{
					if (strpos($sql['Create Table'], 'InnoDB') === false)
					{
						$badTables[] = $table;
					}
				}
				else
				{
					throw new Exception('Tried to run SHOW CREATE TABLE `'.$table.'`, and did not get back expected results.');
				}
			}

			if (!empty($badTables))
			{
				Blocks::log('The following tables are not using InnoDB for storage when they should be: '.implode(', ', $badTables), \CLogger::LEVEL_ERROR);
				throw new Exception('One or more of the database tables are not using InnoDB for storage.');
			}
		}
	}
}
