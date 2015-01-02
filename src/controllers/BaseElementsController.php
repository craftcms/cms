<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use craft\app\errors\HttpException;

/**
 * The BaseElementsController class provides some common methods for [[ElementsController]] and [[ElementIndexController]].
 *
 * Note that all actions in the controller require an authenticated Craft session via [[BaseController::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class BaseElementsController extends BaseController
{
	// Public Methods
	// =========================================================================

	/**
	 * Initializes the application component.
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function init()
	{
		// Element controllers only support JSON responses
		$this->requireAjaxRequest();

		// Element controllers are only available to the Control Panel
		if (!craft()->request->isCpRequest())
		{
			throw new HttpException(403);
		}
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Returns the element type based on the posted element type class.
	 *
	 * @throws Exception
	 * @return BaseElementType
	 */
	protected function getElementType()
	{
		$class = craft()->request->getRequiredParam('elementType');
		$elementType = craft()->elements->getElementType($class);

		if (!$elementType)
		{
			throw new Exception(Craft::t('No element type exists with the class “{class}”', array('class' => $class)));
		}

		return $elementType;
	}

	/**
	 * Returns the context that this controller is being called in.
	 *
	 * @return string
	 */
	protected function getContext()
	{
		return craft()->request->getParam('context');
	}
}
