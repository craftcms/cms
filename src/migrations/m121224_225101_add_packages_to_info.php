<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m121224_225101_add_packages_to_info extends \CDbMigration
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
			if (!$infoTable->getColumn('packages'))
			{
				blx()->db->createCommand()->addColumnAfter('info', 'packages', array('column' => ColumnType::Varchar, 'maxLength' => 200), 'build');

				if (StringHelper::isNotNullOrEmpty(BLOCKS_PACKAGES))
				{
					$result = blx()->db->createCommand()
					                  ->select('id')
					                  ->from('info')
					                  ->queryRow();

					if (isset($result['id']))
					{
						blx()->db->createCommand()->update('info', array('packages' => BLOCKS_PACKAGES), array('id' => $result['id']));

						// Refresh the schema metadata so the rest of the request sees the schema changes.
						blx()->db->getSchema()->refresh();
						InfoRecord::model()->refreshMetaData();
					}
					else
					{
						Blocks::log('Could not find a row in the `info` table', \CLogger::LEVEL_WARNING);
					}
				}
			}
			else
			{
				Blocks::log('The `packages` column already exists in the `info` table.', \CLogger::LEVEL_WARNING);
			}
		}
	}
}
