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
	 * Returns whether this element type can have thumbnails.
	 *
	 * @return bool
	 */
	public function hasThumbs()
	{
		return $this->component->hasThumbs();
	}

	/**
	 * Return a key/label list of the element type's sources.
	 *
	 * @return array|false
	 */
	public function getSources()
	{
		return $this->component->getSources();
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
