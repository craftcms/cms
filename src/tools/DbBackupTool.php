<?php
namespace Craft;

/**
 * Backup Database tool
 *
 * @package craft.app.tools
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

	/**
	 * Returns the tool's icon value.
	 *
	 * @return string
	 */
	public function getIconValue()
	{
		return 'database';
	}

	/**
	 * Returns the tool's options HTML.
	 *
	 * @return string
	 */
	public function getOptionsHtml()
	{
		return craft()->templates->render('_includes/forms/checkbox', array(
			'name'    => 'downloadBackup',
			'label'   => Craft::t('Download backup?'),
			'checked' => true,
		));
	}

	/**
	 * Performs the tool's action.
	 *
	 * @param array $params
	 * @return array
	 */
	public function performAction($params = array())
	{
		$file = craft()->db->backup();

		if (IOHelper::fileExists($file) && isset($params['downloadBackup']) && (bool)$params['downloadBackup'])
		{
			$destZip = craft()->path->getTempPath().IOHelper::getFileName($file, false).'.zip';
			if (IOHelper::fileExists($destZip))
			{
				IOHelper::deleteFile($destZip, true);
			}

			IOHelper::createFile($destZip);

			if (Zip::add($destZip, $file, craft()->path->getDbBackupPath()))
			{
				return array('backupFile' => IOHelper::getFileName($destZip, false));
			}
		}
	}
}
