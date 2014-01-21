<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140204_000005_translatable_relation_fields extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		Craft::log('Setting all relation fields as non-translatable', LogLevel::Info, true);

		$this->update('fields', array(
			'translatable' => 0
		), array('in', 'type', array('Assets', 'Entries', 'Tags', 'Users')));

		Craft::log('Adding the sourceLocale column to the relations table', LogLevel::Info, true);

		$this->addColumnAfter('relations', 'sourceLocale', array('column' => 'locale'), 'sourceId');
		$this->createIndex('relations', 'fieldId,sourceId,sourceLocale,targetId', true);
		$this->addForeignKey('relations', 'sourceLocale', 'locales', 'locale', 'CASCADE', 'CASCADE');

		// Drop the old index now that a new one has been created for the FKs
		$this->dropIndex('relations', 'fieldId,sourceId,targetId', true);

		return true;
	}
}
