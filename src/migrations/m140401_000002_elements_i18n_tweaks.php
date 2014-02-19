<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140401_000002_elements_i18n_tweaks extends BaseMigration
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
			Craft::log('Copying the slugs from entries_i18n into elements_i18n.', LogLevel::Info, true);

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

			Craft::log('Dropping the entries_i18n table.');
			$this->dropTable('entries_i18n');
		}

		if (!craft()->db->columnExists('elements_i18n', 'enabled'))
		{
			Craft::log('Creating an elements_i18n.enabled column.', LogLevel::Info, true);
			$this->addColumnAfter('elements_i18n', 'enabled', array('column' => ColumnType::Bool, 'default' => true), 'uri');
		}

		MigrationHelper::refresh();
		MigrationHelper::dropIndexIfExists('elements_i18n', array('slug', 'locale'));
		MigrationHelper::dropIndexIfExists('elements_i18n', array('enabled'));
		$this->createIndex('elements_i18n', 'slug,locale');
		$this->createIndex('elements_i18n', 'enabled');

		return true;
	}
}
