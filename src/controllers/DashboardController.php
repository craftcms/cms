<?php
namespace Blocks;

/**
 *
 */
class DashboardController extends BaseController
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

		$widgetsPost = blx()->request->getPost('widgets');

		if (blx()->dashboard->saveSettings($widgetsPost))
		{
			blx()->user->setNotice('Dashboard settings saved.');
			$this->redirectToPostedUrl();
		}
		else
		{
			blx()->user->setError('Couldn’t save dashboard settings.');
		}

		$this->loadRequestedTemplate();
	}

	/**
	 * Returns
	 * @throws Exception
	 */
	public function actionGetWidgetHtml()
	{
		$widgetId = blx()->request->getRequiredParam('widgetId');
		$widget = blx()->dashboard->getWidgetById($widgetId);

		if (!$widget)
			throw new Exception(Blocks::t(TranslationCategory::App, 'No widget exists with the ID '.$widgetId));

		$this->loadTemplate('dashboard/_widget', array('widget' => $widget));
	}
}
