<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m120411_233620_remove_triggers extends \CDbMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 */
	public function safeUp()
	{
		$dbPrefix = blx()->config->getDbItem('tablePrefix');

		// We know these tables exists.
		$tables = array(
			'blocks',
			'blocksettings',
			'entries',
			'entryversions',
			'info',
			'languages',
			'licensekeys',
			'entryblocks',
			'sections',
			'sites',
			'systemsettings',
			'users',
			'widgets',
			'widgetsettings',
		);

		// Get the ones that may have been created through content.
		$contentSections = blx()->content->getAllSections();
		foreach ($contentSections as $contentSection)
			$tables[] = $contentSection->getContentTableName();

		foreach ($tables as $table)
		{
			$this->getDbConnection()->createCommand(
				'DROP TRIGGER IF EXISTS `'.$dbPrefix.'_auditinfoinsert_'.$table.'`'
			)->execute();

			$this->getDbConnection()->createCommand(
				'DROP TRIGGER IF EXISTS `'.$dbPrefix.'_auditinfoupdate_'.$table.'`'
			)->execute();
		}
	}
}
