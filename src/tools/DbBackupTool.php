<?php
namespace Craft;

/**
 * Backup Database tool
 */
class DbBackupTool extends BaseTool
{
	/**
	 * Returns the tool name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Backup Database');
	}
}
