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
	 * Saves a widget.
	 */
	public function actionSaveWidget()
	{
		$this->requirePostRequest();

		$widgetId = blx()->request->getPost('widgetId');
		$class    = blx()->request->getRequiredPost('class');

		$widgetSettings = blx()->request->getPost('types');
		$settings['class'] = $class;
		$settings['settings'] = isset($widgetSettings[$class]) ? $widgetSettings[$class] : null;

		$widget = blx()->dashboard->saveWidget($settings, $widgetId);

		// Did it save?
		if (!$widget->getSettings()->hasErrors() && !$widget->record->hasErrors())
		{
			blx()->user->setNotice(Blocks::t('Widget saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			blx()->user->setError(Blocks::t('Couldn’t save widget.'));
		}

		// Reload the original template
		$this->renderRequestedTemplate(array(
			'widget' => $widget
		));
	}

	/**
	 * Deletes a widget.
	 */
	public function actionDeleteWidget()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$widgetId = Json::decode(blx()->request->getRequiredPost('widgetId'));
		blx()->dashboard->deleteWidget($widgetId);
		$this->returnJson(array('success' => true));
	}

	/**
	 * Reorders widgets.
	 */
	public function actionReorderWidgets()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$widgetIds = Json::decode(blx()->request->getRequiredPost('widgetIds'));
		blx()->dashboard->reorderWidgets($widgetIds);
		$this->returnJson(array('success' => true));
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
			throw new Exception(Blocks::t('No widget exists with the ID “{id}”.', array('id' => $widgetId)));

		$this->renderTemplate('dashboard/_widget', array(
			'class' => $widget->getClassHandle(),
			'title' => $widget->getTitle(),
			'body'  => $widget->getBodyHtml()
		));
	}
}
