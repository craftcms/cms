<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\base\ToolInterface;
use craft\app\errors\HttpException;
use craft\app\helpers\ComponentHelper;
use craft\app\helpers\IOHelper;
use craft\app\web\Controller;

/**
 * The ToolsController class is a controller that handles various tools related tasks such as trigger tool actions.
 *
 * Note that all actions in this controller require administrator access in order to execute.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ToolsController extends Controller
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc Controller::init()
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function init()
	{
		// All tool actions require an admin.
		$this->requireAdmin();

		// Any actions here require all we can get.
		Craft::$app->config->maxPowerCaptain();
	}

	/**
	 * Performs a tool's action.
	 *
	 * @return null
	 */
	public function actionPerformAction()
	{
		$this->requirePostRequest();

		$class = Craft::$app->getRequest()->getRequiredBodyParam('tool');
		$params = Craft::$app->getRequest()->getBodyParam('params', []);

		/** @var ToolInterface $tool */
		$tool = ComponentHelper::createComponent($class, 'craft\app\base\ToolInterface');
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
		$filename = Craft::$app->getRequest()->getRequiredQueryParam('filename');

		if (($filePath = IOHelper::fileExists(Craft::$app->path->getTempPath().'/'.$filename.'.zip')) == true)
		{
			Craft::$app->getRequest()->sendFile(IOHelper::getFilename($filePath), IOHelper::getFileContents($filePath), ['forceDownload' => true]);
		}
	}
}
