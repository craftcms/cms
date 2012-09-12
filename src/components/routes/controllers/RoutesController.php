<?php
namespace Blocks;

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

		$urlParts = blx()->request->getRequiredPost('url');
		$template = blx()->request->getRequiredPost('template');
		$routeId = blx()->request->getPost('routeId');

		$route = blx()->routes->saveRoute($urlParts, $template, $routeId);

		if ($route->hasErrors())
			$this->returnJson(array('errors' => $route->getErrors()));
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

		$routeId = blx()->request->getRequiredPost('routeId');
		blx()->routes->deleteRoute($routeId);

		$this->returnJson(array('success' => true));
	}

	/**
	 * Updates the route order.
	 */
	public function actionUpdateRouteOrder()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$routeIds = blx()->request->getRequiredPost('routeIds');
		blx()->routes->updateRouteOrder($routeIds);

		$this->returnJson(array('success' => true));
	}

}
