<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140401_000019_editions extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if (craft()->db->columnExists('info', 'packages'))
		{
			// Figure out if they had any packages
			$hadPackages = (bool) craft()->db->createCommand()
				->select('packages')
				->from('info')
				->queryScalar();

			// Drop the packages column
			$this->dropColumn('info', 'packages');

			// Add the new edition column
			$this->addColumnAfter('info', 'edition', array('column' => ColumnType::TinyInt, 'length' => 1, 'unsigned' => true, 'default' => 0, 'null' => false), 'releaseDate');

			// If they had any packages, set this to Pro edition
			if ($hadPackages)
			{
				$info = craft()->getInfo();
				$info->edition = 2;
				craft()->saveInfo($info);
			}
		}

		return true;
	}
}
