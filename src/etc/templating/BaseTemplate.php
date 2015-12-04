<?php
namespace Craft;

/**
 * Base Twig template class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.templating
 * @since     2.0
 */
abstract class BaseTemplate extends \Twig_Template
{
	// Protected Methods
	// =========================================================================

	/**
	 * Displays the template.
	 */
	protected function displayWithErrorHandling(array $context, array $blocks = array())
	{
		try
		{
			parent::displayWithErrorHandling($context, $blocks);
		}
		catch (\Twig_Error_Runtime $e)
		{
			if (craft()->config->get('suppressTemplateErrors'))
			{
				// Just log it and move on
				craft()->errorHandler->logException($e);
			}
			else
			{
				throw $e;
			}
		}
	}

	/**
	 * Returns the attribute value for a given array/object.
	 *
	 * @param mixed  $object            The object or array from where to get the item
	 * @param mixed  $item              The item to get from the array or object
	 * @param array  $arguments         An array of arguments to pass if the item is an object method
	 * @param string $type              The type of attribute (@see \Twig_Template constants)
	 * @param bool   $isDefinedTest     Whether this is only a defined check
	 * @param bool   $ignoreStrictCheck Whether to ignore the strict attribute check or not
	 *
	 * @throws \Twig_Error_Runtime If the attribute does not exist and Twig is running in strict mode and $isDefinedTest
	 *                             is false
	 * @return mixed               The attribute value, or a bool when $isDefinedTest is true, or null when the
	 *                             attribute is not set and $ignoreStrictCheck is true
	 */
	protected function getAttribute($object, $item, array $arguments = array(), $type = \Twig_Template::ANY_CALL, $isDefinedTest = false, $ignoreStrictCheck = false)
	{
		if (is_object($object) && $object instanceof BaseElementModel)
		{
			$this->_includeElementInTemplateCaches($object);
		}

		return parent::getAttribute($object, $item, $arguments, $type, $isDefinedTest, $ignoreStrictCheck);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Includes this element in any active template caches.
	 *
	 * @param BaseElementModel $element
	 *
	 * @return null
	 */
	private function _includeElementInTemplateCaches(BaseElementModel $element)
	{
		$elementId = $element->id;

		if ($elementId)
		{
			// Don't initialize the CacheService if we don't have to
			$cacheService = craft()->getComponent('templateCache', false);

			if ($cacheService)
			{
				$cacheService->includeElementInTemplateCaches($elementId);
			}
		}
	}
}
