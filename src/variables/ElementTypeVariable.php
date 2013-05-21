<?php
namespace Craft;

/**
 * Element type template variable
 */
class ElementTypeVariable extends BaseComponentTypeVariable
{
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
	 * Returns elements, possibly within a given source.
	 *
	 * @param string|null $source
	 * @return array
	 */
	public function getElements($source = null)
	{
		$criteriaAttributes = null;

		if ($source)
		{
			$sources = $this->component->getSources();

			if (is_array($sources) && isset($sources[$source]))
			{
				$criteriaAttributes = $sources[$source]['criteria'];
			}
		}

		$criteria = craft()->elements->getCriteria($this->component->getClassHandle(), $criteriaAttributes);
		return craft()->elements->findElements($criteria);
	}

	/**
	 * Returns the table attributes for a given source.
	 *
	 * @param string|null $source
	 * @return array
	 */
	public function getTableAttributes($source = null)
	{
		return $this->component->defineTableAttributes($source);
	}
}
