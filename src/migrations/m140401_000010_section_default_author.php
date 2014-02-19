<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140401_000010_section_default_author extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if (!craft()->db->columnExists('sections', 'defaultAuthorId'))
		{
			Craft::log('Adding defaultAuthorId column after structureId to sections table.', LogLevel::Info, true);
			$this->addColumnAfter('sections', 'defaultAuthorId', array('column' => ColumnType::Int), 'structureId');

			Craft::log('Adding foreign key to from sections to users table for default author.', LogLevel::Info, true);
			$this->addForeignKey('sections', 'defaultAuthorId', 'users', 'id', 'SET NULL');
		}
		else
		{
			Craft::log('Tried to add the defaultAuthorId column to the sections table, but it already exists.', LogLevel::Warning, true);
		}

		return true;
	}
}
