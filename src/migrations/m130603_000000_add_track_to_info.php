<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130603_000000_add_track_to_info extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$infoTable = $this->dbConnection->schema->getTable('{{info}}');

		if ($infoTable)
		{
			if (($trackColumn = $infoTable->getColumn('track')) == null)
			{
				Craft::log('Adding `track` column to the `info` table.', LogLevel::Info, true);
				$this->addColumnAfter('info', 'track', array(AttributeType::String, 'maxLength' => 40, 'column' => ColumnType::Varchar, 'required' => true), 'maintenance');
				Craft::log('Added `track` column to the `info` table.', LogLevel::Info, true);

				$this->update('info',
					array('track' => 'stable')
				);

				Craft::log('Set `track` column to `stable` successfully.', LogLevel::Info, true);
			}
			else
			{
				Craft::log('Tried to add a `track` column to the `info` table, but there is already one there.', LogLevel::Warning);
			}
		}
		else
		{
			Craft::log('Could not find an `info` table. Wut?', LogLevel::Error);
		}
	}
}
