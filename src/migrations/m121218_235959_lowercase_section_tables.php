<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m121218_235959_lowercase_section_tables extends \CDbMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$criteria = new SectionCriteria();
			$sections = blx()->sections->findSections($criteria);

			foreach ($sections as $section)
			{
				$lcHandle = strtolower($section->handle);

				if ($section->handle != $lcHandle)
				{
					$oldTable = 'entrycontent_'.$section->handle;
					$newTable = 'entrycontent_'.$lcHandle;
					$tmpTable = 'tmp_'.strtolower(StringHelper::randomString());

					blx()->db->createCommand()->renameTable($oldTable, $tmpTable);
					blx()->db->createCommand()->renameTable($tmpTable, $newTable);
				}
			}
		}

		return true;
	}
}
