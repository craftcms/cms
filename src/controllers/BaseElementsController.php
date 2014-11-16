<?php
namespace Craft;

/**
 * The BaseElementsController class provides some common methods for {@link ElementsController} and {@link ElementIndexController}.
 *
 * Note that all actions in the controller require an authenticated Craft session via {@link BaseController::allowAnonymous}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.controllers
 * @since     2.3
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
