<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130204_221240_add_minrequiredbuild_to_info extends DbMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @throws Exception
	 * @return bool
	 */
	public function safeUp()
	{
		$infoTable = blx()->db->schema->getTable('{{info}}');

		if ($infoTable)
		{
			if (!$infoTable->getColumn('minRequiredBuild'))
			{
				blx()->db->createCommand()->addColumnAfter('info', 'minRequiredBuild', array('column' => ColumnType::Int, 'unsigned' => true, 'required' => true), 'maintenance');

				if (StringHelper::isNotNullOrEmpty(BLOCKS_MIN_BUILD_REQUIRED))
				{
					$result = blx()->db->createCommand()
					                  ->select('id')
					                  ->from('info')
					                  ->queryRow();

					if (isset($result['id']))
					{
						blx()->db->createCommand()->update('info', array('minRequiredBuild' => BLOCKS_MIN_BUILD_REQUIRED), array('id' => $result['id']));

						// Refresh the schema metadata so the rest of the request sees the schema changes.
						blx()->db->getSchema()->refresh();
						InfoRecord::model()->refreshMetaData();
					}
					else
					{
						Blocks::log('Could not find a row in the `info` table', \CLogger::LEVEL_WARNING);
					}
				}
				else
				{
					Blocks::log('Could not find the BLOCKS_MIN_BUILD_REQUIRED constant.', \CLogger::LEVEL_ERROR);
					throw new Exception('Could not find the BLOCKS_MIN_BUILD_REQUIRED constant.');
				}
			}
			else
			{
				Blocks::log('The `minRequiredBuild` column already exists in the `info` table.', \CLogger::LEVEL_WARNING);
			}
		}
	}
}
