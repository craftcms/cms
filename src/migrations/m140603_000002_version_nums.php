<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140603_000002_version_nums extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if (!craft()->db->columnExists('entryversions', 'num'))
		{
			// Add the `num` column
			$columnConfig = array('maxLength' => 6, 'decimals' => 0, 'column' => 'smallint', 'unsigned' => true);
			$this->addColumnAfter('entryversions', 'num', $columnConfig, 'locale');

			$versionCountsByEntryIdAndLocale = array();

			// Populate it in batches
			$offset = 0;
			do
			{
				$versions = craft()->db->createCommand()
					->select('id, entryId, locale')
					->from('entryversions')
					->order('dateCreated asc')
					->offset($offset)
					->limit(100)
					->queryAll();

				foreach ($versions as $version)
				{
					$entryId = $version['entryId'];
					$locale  = $version['locale'];

					if (!isset($versionCountsByEntryIdAndLocale[$entryId][$locale]))
					{
						$versionCountsByEntryIdAndLocale[$entryId][$locale] = 1;
					}
					else
					{
						$versionCountsByEntryIdAndLocale[$entryId][$locale]++;
					}

					$this->update('entryversions', array(
						'num' => $versionCountsByEntryIdAndLocale[$entryId][$locale]
					), array(
						'id' => $version['id'])
					);
				}

				$offset += 100;
			}
			while (count($versions) == 100);

			// Set the `num` column to NOT NULL
			$columnConfig['null'] = false;
			$this->alterColumn('entryversions', 'num', $columnConfig);
		}

		return true;
	}
}
