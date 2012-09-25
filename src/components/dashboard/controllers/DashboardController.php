<?php
namespace Blocks;

/**
 *
 */
class DashboardController extends BaseController
{
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

		$widgetPackage = new WidgetPackage();
		$widgetPackage->id = blx()->request->getPost('widgetId');
		$widgetPackage->type = blx()->request->getRequiredPost('class');

		$typeSettings = blx()->request->getPost('types');
		if (isset($typeSettings[$widgetPackage->type]))
		{
			$widgetPackage->settings = $typeSettings[$widgetPackage->type];
		}

		// Did it save?
		if ($widgetPackage->save())
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
			'widgetPackage' => $widgetPackage
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
		blx()->dashboard->deleteUserWidgetById($widgetId);
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
		$widgetPackage = blx()->dashboard->getUserWidgetById($widgetId);

		if (!$widgetPackage)
			throw new Exception(Blocks::t('No widget exists with the ID “{id}”.', array('id' => $widgetId)));

		$widget = blx()->dashboard->getWidgetByClass($widgetPackage->type);

		if (!$widget)
			throw new Exception(Blocks::t('No widget exists with the class “{class}”.', array('class' => $widgetPackage->type)));

		$widget->setSettings($widgetPackage->settings);

		$this->renderTemplate('dashboard/_widget', array(
			'class' => $widget->getClassHandle(),
			'title' => $widget->getTitle(),
			'body'  => $widget->getBodyHtml()
		));
	}
}
