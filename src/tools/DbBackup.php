<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\tools;

use craft\app\Craft;

/**
 * Backup Database tool
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class DbBackup extends BaseTool
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ComponentTypeInterface::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Backup Database');
	}

	/**
	 * @inheritDoc ToolInterface::getIconValue()
	 *
	 * @return string
	 */
	public function getIconValue()
	{
		return 'database';
	}

	/**
	 * @inheritDoc ToolInterface::getOptionsHtml()
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
	 * @inheritDoc ToolInterface::performAction()
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	public function performAction($params = array())
	{
		// In addition to the default tables we want to ignore data in, we also don't care about data in the session
		// table in this tools' case.
		$file = craft()->db->backup(array('sessions'));

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
