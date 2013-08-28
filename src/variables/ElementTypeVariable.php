<?php
namespace Craft;

/**
 * Element type template variable
 */
class ElementTypeVariable extends BaseComponentTypeVariable
{
	/**
	 * Returns whether this element type can have statuses.
	 *
	 * @return bool
	 */
	public function hasStatuses()
	{
		return $this->component->hasStatuses();
	}

	/**
	 * Return a key/label list of the element type's sources.
	 *
	 * @param string|null $context
	 * @return array|false
	 */
	public function getSources($context = null)
	{
		return $this->component->getSources($context);
	}

	/**
	 * Returns whether this element type can have titles.
	 *
	 * @return bool
	 */
	public function hasTitles()
	{
		return $this->component->hasTitles();
	}

}
