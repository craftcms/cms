<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140401_100002_delete_compiled_templates extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		IOHelper::clearFolder(craft()->path->getCompiledTemplatesPath(), true);

		return true;
	}
}
