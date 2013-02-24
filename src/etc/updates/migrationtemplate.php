<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of {MigrationNameDesc}
 */
class {ClassName} extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		return true;
	}
}
