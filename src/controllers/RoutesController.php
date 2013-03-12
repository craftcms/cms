<?php
namespace Craft;

/**
 * Handles route actions.
 */
class RoutesController extends BaseController
{
	/**
	 * Saves a new or existing route.
	 */
	public function actionSaveRoute()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$urlParts = craft()->request->getRequiredPost('url');
		$template = craft()->request->getRequiredPost('template');
		$routeId = craft()->request->getPost('routeId');

		$route = craft()->routes->saveRoute($urlParts, $template, $routeId);

		if ($route->hasErrors())
		{
			$this->returnJson(array('errors' => $route->getErrors()));
		}
		else
		{
			$this->returnJson(array(
				'success' => true,
				'routeId' => $route->id
			));
		}
	}

	/**
	 * Deletes a route.
	 */
	public function actionDeleteRoute()
	{
		$this->requirePostRequest();

		$routeId = craft()->request->getRequiredPost('routeId');
		craft()->routes->deleteRouteById($routeId);

		$this->returnJson(array('success' => true));
	}

	/**
	 * Updates the route order.
	 */
	public function actionUpdateRouteOrder()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$routeIds = craft()->request->getRequiredPost('routeIds');
		craft()->routes->updateRouteOrder($routeIds);

		$this->returnJson(array('success' => true));
	}

}
