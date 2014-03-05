<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140401_000011_categories extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Create the categorygroups table
		if (!craft()->db->tableExists('categorygroups'))
		{
			Craft::log('Creating the categorygroups table', LogLevel::Info, true);

			$this->createTable('categorygroups', array(
				'structureId'   => array('column' => ColumnType::Int, 'null' => false),
				'fieldLayoutId' => array('column' => ColumnType::Int),
				'name'          => array('column' => ColumnType::Varchar, 'required' => true),
				'handle'        => array('column' => ColumnType::Varchar, 'required' => true),
				'hasUrls'       => array('column' => ColumnType::Bool, 'required' => true, 'default' => true),
				'template'      => array('column' => ColumnType::Varchar, 'maxLength' => 500),
			));

			$this->createIndex('categorygroups', 'name', true);
			$this->createIndex('categorygroups', 'handle', true);
			$this->addForeignKey('categorygroups', 'structureId', 'structures', 'id', 'CASCADE');
			$this->addForeignKey('categorygroups', 'fieldLayoutId', 'fieldlayouts', 'id', 'SET NULL');
		}

		// Create the categorygroups_i18n table
		if (!craft()->db->tableExists('categorygroups_i18n'))
		{
			Craft::log('Creating the categorygroups_i18n table', LogLevel::Info, true);

			$this->createTable('categorygroups_i18n', array(
				'groupId'         => array('column' => ColumnType::Int, 'required' => true),
				'locale'          => array('column' => ColumnType::Locale, 'required' => true),
				'urlFormat'       => array('column' => ColumnType::Varchar),
				'nestedUrlFormat' => array('column' => ColumnType::Varchar),
			));

			$this->createIndex('categorygroups_i18n', 'groupId,locale', true);
			$this->addForeignKey('categorygroups_i18n', 'groupId', 'categorygroups', 'id', 'CASCADE');
			$this->addForeignKey('categorygroups_i18n', 'locale', 'locales', 'locale', 'CASCADE', 'CASCADE');
		}

		// Create the categories table
		if (!craft()->db->tableExists('categories'))
		{
			Craft::log('Creating the categories table', LogLevel::Info, true);

			$this->createTable('categories', array(
				'id'      => array('column' => ColumnType::Int, 'required' => true, 'primaryKey' => true),
				'groupId' => array('column' => ColumnType::Int, 'required' => true),
			), null, false);

			$this->addForeignKey('categories', 'id', 'elements', 'id', 'CASCADE');
			$this->addForeignKey('categories', 'groupId', 'categorygroups', 'id', 'CASCADE');
		}

		return true;
	}
}
