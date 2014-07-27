<?php
namespace Craft;

/**
 * Base Twig template class.
 *
 * @abstract
 * @package craft.app.etc.templating
 */
abstract class BaseTemplate extends \Twig_Template
{
	/**
	 * Returns the attribute value for a given array/object.
	 *
	 * @param mixed   $object            The object or array from where to get the item
	 * @param mixed   $item              The item to get from the array or object
	 * @param array   $arguments         An array of arguments to pass if the item is an object method
	 * @param string  $type              The type of attribute (@see \Twig_Template constants)
	 * @param Boolean $isDefinedTest     Whether this is only a defined check
	 * @param Boolean $ignoreStrictCheck Whether to ignore the strict attribute check or not
	 *
	 * @return mixed The attribute value, or a Boolean when $isDefinedTest is true, or null when the attribute is not set and $ignoreStrictCheck is true
	 *
	 * @throws \Twig_Error_Runtime if the attribute does not exist and Twig is running in strict mode and $isDefinedTest is false
	 */
	protected function getAttribute($object, $item, array $arguments = array(), $type = \Twig_Template::ANY_CALL, $isDefinedTest = false, $ignoreStrictCheck = false)
	{
		if (is_object($object) && $object instanceof BaseElementModel)
		{
			$this->_includeElementInTemplateCaches($object);
		}

		return parent::getAttribute($object, $item, $arguments, $type, $isDefinedTest, $ignoreStrictCheck);
	}

	/**
	 * Includes this element in any active template caches.
	 *
	 * @access private
	 * @param BaseElementModel $element
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
