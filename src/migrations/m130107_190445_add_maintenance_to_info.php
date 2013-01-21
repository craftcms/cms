<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130107_190445_add_maintenance_to_info extends DbMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$infoTable = blx()->db->schema->getTable('{{info}}');

		if ($infoTable)
		{
			if (!$infoTable->getColumn('maintenance'))
			{
				blx()->db->createCommand()->addColumnAfter('info', 'maintenance', AttributeType::Bool, 'on');

				$result = blx()->db->createCommand()
				                  ->select('id')
				                  ->from('info')
				                  ->queryRow();

				if (isset($result['id']))
				{
					blx()->db->createCommand()->update('info', array('maintenance' => 0), array('id' => $result['id']));

					// Refresh the schema metadata so the rest of the request sees the schema changes.
					blx()->db->getSchema()->refresh();
					InfoRecord::model()->refreshMetaData();
				}
				else
				{
					Blocks::log('Could not find a row in the `info` table', \CLogger::LEVEL_WARNING);
				}

				// Refresh the schema metadata so the rest of the request sees the schema changes.
				blx()->db->getSchema()->refresh();
				InfoRecord::model()->refreshMetaData();
			}
			else
			{
				Blocks::log('The `maintenance` column already exists in the `info` table.', \CLogger::LEVEL_WARNING);
			}
		}
	}
}
