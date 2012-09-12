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
	public function actionSaveUserWidget()
	{
		$this->requirePostRequest();

		$widgetId = blx()->request->getPost('widgetId');
		$class    = blx()->request->getRequiredPost('class');

		$widgetSettings = blx()->request->getPost('types');
		$settings['class'] = $class;
		$settings['settings'] = isset($widgetSettings[$class]) ? $widgetSettings[$class] : null;

		$widget = blx()->dashboard->saveUserWidget($settings, $widgetId);

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
			'widget' => new WidgetVariable($widget)
		));
	}

	/**
	 * Deletes a widget.
	 */
	public function actionDeleteUserWidget()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$widgetId = JsonHelper::decode(blx()->request->getRequiredPost('widgetId'));
		blx()->dashboard->deleteUserWidget($widgetId);
		$this->returnJson(array('success' => true));
	}

	/**
	 * Reorders widgets.
	 */
	public function actionReorderUserWidgets()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$widgetIds = JsonHelper::decode(blx()->request->getRequiredPost('widgetIds'));
		blx()->dashboard->reorderUserWidgets($widgetIds);
		$this->returnJson(array('success' => true));
	}

	/**
	 * Returns
	 * @throws Exception
	 */
	public function actionGetWidgetHtml()
	{
		$widgetId = blx()->request->getRequiredParam('widgetId');
		$widget = blx()->dashboard->getUserWidgetById($widgetId);

		if (!$widget)
			throw new Exception(Blocks::t('No widget exists with the ID “{id}”.', array('id' => $widgetId)));

		$this->renderTemplate('dashboard/_widget', array(
			'class' => $widget->getClassHandle(),
			'title' => $widget->getTitle(),
			'body'  => $widget->getBodyHtml()
		));
	}
}
