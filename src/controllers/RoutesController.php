<?php
namespace Craft;

/**
 * Handles route actions.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.controllers
 * @since     1.0
 */
class RoutesController extends BaseController
{
	// Public Methods
	// =========================================================================

	/**
	 * Saves a new or existing route.
	 *
	 * @return null
	 */
	public function actionSaveRoute()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$urlParts = craft()->request->getRequiredPost('url');
		$template = craft()->request->getRequiredPost('template');
		$routeId  = craft()->request->getPost('routeId');
		$locale   = craft()->request->getPost('locale');

		if ($locale === '')
		{
			$locale = null;
		}

		$routeRecord = craft()->routes->saveRoute($urlParts, $template, $routeId, $locale);

		if ($routeRecord->hasErrors())
		{
			$this->returnJson(array('errors' => $routeRecord->getErrors()));
		}
		else
		{
			$this->returnJson(array(
				'success' => true,
				'routeId' => $routeRecord->id,
				'locale'  => $routeRecord->locale
			));
		}
	}

	/**
	 * Deletes a route.
	 *
	 * @return null
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
	 *
	 * @return null
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
