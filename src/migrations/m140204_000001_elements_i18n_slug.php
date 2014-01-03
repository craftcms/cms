<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140204_000001_elements_i18n_slug extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if (!craft()->db->columnExists('elements_i18n', 'slug'))
		{
			Craft::log('Creating an elements_i18n.slug column.', LogLevel::Info, true);
			$this->addColumnAfter('elements_i18n', 'slug', ColumnType::Varchar, 'locale');
		}

		if (craft()->db->tableExists('entries_i18n'))
		{
			$rows = craft()->db->createCommand()
				->select('entryId, locale, slug')
				->from('entries_i18n')
				->queryAll();

			foreach ($rows as $row)
			{
				$this->update('elements_i18n', array(
					'slug' => $row['slug']
				), array(
					'elementId' => $row['entryId'],
					'locale'    => $row['locale']
				));
			}

			$this->dropTable('entries_i18n');
		}

		return true;
	}
}
