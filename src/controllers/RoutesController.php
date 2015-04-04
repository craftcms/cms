<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\errors\HttpException;
use craft\app\web\Controller;

/**
 * The RoutesController class is a controller that handles various route related tasks such as saving, deleting and
 * re-ordering routes in the control panel.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class RoutesController extends Controller
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 * @throws HttpException if the user isn’t an admin
	 */
	public function init()
	{
		// All route actions require an admin
		$this->requireAdmin();
	}

	/**
	 * Saves a new or existing route.
	 *
	 * @return null
	 */
	public function actionSaveRoute()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$urlParts = Craft::$app->getRequest()->getRequiredBodyParam('url');
		$template = Craft::$app->getRequest()->getRequiredBodyParam('template');
		$routeId  = Craft::$app->getRequest()->getBodyParam('routeId');
		$locale   = Craft::$app->getRequest()->getBodyParam('locale');

		if ($locale === '')
		{
			$locale = null;
		}

		$routeRecord = Craft::$app->routes->saveRoute($urlParts, $template, $routeId, $locale);

		if ($routeRecord->hasErrors())
		{
			$this->returnJson(['errors' => $routeRecord->getErrors()]);
		}
		else
		{
			$this->returnJson([
				'success' => true,
				'routeId' => $routeRecord->id,
				'locale'  => $routeRecord->locale
			]);
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

		$routeId = Craft::$app->getRequest()->getRequiredBodyParam('routeId');
		Craft::$app->routes->deleteRouteById($routeId);

		$this->returnJson(['success' => true]);
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

		$routeIds = Craft::$app->getRequest()->getRequiredBodyParam('routeIds');
		Craft::$app->routes->updateRouteOrder($routeIds);

		$this->returnJson(['success' => true]);
	}

}
