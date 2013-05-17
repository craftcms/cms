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
	}

	/**
	 * Permorms a tool's action.
	 */
	public function actionPerformAction()
	{
		$this->requirePostRequest();

		$class = craft()->request->getRequiredPost('tool');

		$tool = craft()->components->getComponentByTypeAndClass(ComponentType::Tool, $class);

		$response = $tool->performAction($_POST);
		$this->returnJson($response);
	}
}
