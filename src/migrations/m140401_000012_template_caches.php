<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140401_000012_template_caches extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if (!craft()->db->tableExists('templatecaches'))
		{
			Craft::log('Creating the templatecaches table.', LogLevel::Info, true);

			$this->createTable('templatecaches', array(
				'cacheKey'   => array('column' => ColumnType::Varchar, 'length' => 36, 'null' => false),
				'locale'     => array('column' => ColumnType::Locale),
				'path'       => array('column' => ColumnType::Varchar),
				'expiryDate' => array('column' => ColumnType::DateTime, 'null' => false),
				'body'       => array('column' => ColumnType::MediumText, 'null' => false),
			), null, true, false);

			$this->createIndex('templatecaches', 'expiryDate,cacheKey,locale,path');
			$this->addForeignKey('templatecaches', 'locale', 'locales', 'locale', 'CASCADE', 'CASCADE');
		}

		if (!craft()->db->tableExists('templatecacheelements'))
		{
			Craft::log('Creating the templatecacheelements table.', LogLevel::Info, true);

			$this->createTable('templatecacheelements', array(
				'cacheId'   => array('column' => ColumnType::Int, 'null' => false),
				'elementId' => array('column' => ColumnType::Int, 'null' => false),
			), null, false, false);

			$this->addForeignKey('templatecacheelements', 'cacheId', 'templatecaches', 'id', 'CASCADE', null);
			$this->addForeignKey('templatecacheelements', 'elementId', 'elements', 'id', 'CASCADE', null);
		}

		return true;
	}
}
