<?php
namespace Craft;

/**
 * The ToolsController class is a controller that handles various tools related tasks such as trigger tool actions.
 *
 * Note that all actions in this controller require administrator access in order to execute.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.controllers
 * @since     1.0
 */
class ToolsController extends BaseController
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseController::init()
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function init()
	{
		// All tool actions require an admin.
		craft()->userSession->requireAdmin();

		// Any actions here require all we can get.
		craft()->config->maxPowerCaptain();
	}

	/**
	 * Performs a tool's action.
	 *
	 * @return null
	 */
	public function actionPerformAction()
	{
		$this->requirePostRequest();

		$class = craft()->request->getRequiredPost('tool');
		$params = craft()->request->getPost('params', array());

		$tool = craft()->components->getComponentByTypeAndClass(ComponentType::Tool, $class);

		$response = $tool->performAction($params);
		$this->returnJson($response);
	}

	/**
	 * Returns a database backup zip file to the browser.
	 *
	 * @return null
	 */
	public function actionDownloadBackupFile()
	{
		$fileName = craft()->request->getRequiredQuery('fileName');

		if (($filePath = IOHelper::fileExists(craft()->path->getTempPath().$fileName.'.zip')) == true)
		{
			craft()->request->sendFile(IOHelper::getFileName($filePath), IOHelper::getFileContents($filePath), array('forceDownload' => true));
		}
	}
}
