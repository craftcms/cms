<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140401_000005_translatable_matrix_fields extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if (!craft()->db->columnExists('matrixblocks', 'ownerLocale'))
		{
			Craft::log('Setting all Matrix fields as non-translatable', LogLevel::Info, true);

			$this->update('fields', array(
				'translatable' => 0
			), array(
				'type' => 'Matrix'
			));

			Craft::log('Adding the ownerLocale column to the matrixblocks table', LogLevel::Info, true);

			$this->addColumnAfter('matrixblocks', 'ownerLocale', array('column' => 'locale'), 'ownerId');
			$this->addForeignKey('matrixblocks', 'ownerLocale', 'locales', 'locale', 'CASCADE', 'CASCADE');
		}

		return true;
	}
}
