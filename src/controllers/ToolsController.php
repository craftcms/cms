<?php
namespace Craft;

/**
 * Handles tool actions.
 */
class ToolsController extends BaseController
{
	/**
	 * Init
	 */
	public function init()
	{
		// All tool actions require an admin
		craft()->userSession->requireAdmin();

		craft()->config->maxPowerCaptain();
	}

	/**
	 * Performs a tool's action.
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
