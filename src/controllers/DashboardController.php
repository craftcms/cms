<?php
namespace Blocks;

/**
 *
 */
class DashboardController extends Controller
{
	/**
	 * All dashboard actions require the user to be logged in
	 */
	public function init()
	{
		$this->requireLogin();
	}

	/**
	 */
	public function actionGetAlerts()
	{
		$alerts = DashboardHelper::getAlerts(true);
		$r = array('alerts' => $alerts);
		$this->returnJson($r);
	}

	/**
	 * Saves the user's dashboard settings
	 */
	public function actionSaveSettings()
	{
		$this->requirePostRequest();

		$widgetsPost = b()->request->getPost('widgets');

		if (b()->dashboard->saveSettings($widgetsPost))
		{
			b()->user->setMessage(MessageType::Notice, 'Dashboard settings saved.');
			$this->redirectToPostedUrl();
		}
		else
		{
			b()->user->setMessage(MessageType::Error, 'Couldn’t save dashboard settings.');
		}

		$this->loadRequestedTemplate();
	}

	/**
	 * Returns 
	 */
	public function actionGetWidgetHtml()
	{
		$widgetId = b()->request->getRequiredParam('widgetId');
		$widget = b()->dashboard->getWidgetById($widgetId);
		if (!$widget)
			throw new Exception('No widget exists with the ID '.$widgetId);

		$this->loadTemplate('dashboard/_widget', array('widget' => $widget));
	}
}
