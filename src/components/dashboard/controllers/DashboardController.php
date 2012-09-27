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

		$widget = new WidgetPackage();
		$widget->id = blx()->request->getPost('widgetId');
		$widget->type = blx()->request->getRequiredPost('type');

		$typeSettings = blx()->request->getPost('types');
		if (isset($typeSettings[$widget->type]))
		{
			$widget->settings = $typeSettings[$widget->type];
		}

		// Did it save?
		if ($widget->save())
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
	public function actionDeleteUserWidget()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$widgetId = JsonHelper::decode(blx()->request->getRequiredPost('id'));
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

		$widgetIds = JsonHelper::decode(blx()->request->getRequiredPost('ids'));
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

		$widgetType = blx()->dashboard->getWidgetType($widget->type);

		if (!$widgetType)
			throw new Exception(Blocks::t('No widget exists with the class “{class}”.', array('class' => $widget->type)));

		$widgetType->setSettings($widget->settings);

		$this->renderTemplate('dashboard/_widget', array(
			'class' => $widgetType->getClassHandle(),
			'title' => $widgetType->getTitle(),
			'body'  => $widgetType->getBodyHtml()
		));
	}
}
