<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130307_151756_add_transform_index extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$assetTransformIndexTable = $this->dbConnection->schema->getTable('{{assettransformindex}}');

		if (!$assetTransformIndexTable)
		{

			$this->createTable('assettransformindex', array(
				'fileId'       => array('maxLength' => 11, 'column' => ColumnType::Int, 'required' => true),
				'location'     => array('maxLength' => 255, 'column' => ColumnType::Varchar, 'required' => true),
				'sourceId'     => array('maxLength' => 11, 'column' => ColumnType::Int, 'required' => true),
				'fileExists'   => ColumnType::Bool,
				'inProgress'   => ColumnType::Bool,
				'dateIndexed'  => AttributeType::DateTime,
			));

			$this->createIndex('assettransformindex', 'sourceId, fileId, location');

			Craft::log('Successfully created the `assettransformindex` table.', \CLogger::LEVEL_INFO);
		}
		else
		{
			Craft::log('Tried to add the `assettransformindex` table, but it looks like it already exists.', \CLogger::LEVEL_WARNING);
		}

		return true;
	}
}
