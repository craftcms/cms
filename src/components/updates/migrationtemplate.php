<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of {MigrationNameDesc}
 */
class {ClassName} extends DbMigration
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
