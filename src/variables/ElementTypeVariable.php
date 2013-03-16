<?php
namespace Craft;

/**
 * Element type template variable
 */
class ElementTypeVariable extends BaseComponentTypeVariable
{
	/**
	 * Returns the element type's link settings HTML.
	 *
	 * @return string
	 */
	public function getLinkSettingsHtml()
	{
		return $this->component->getLinkSettingsHtml();
	}

	/**
	 * Return a key/label list of the element type's sources.
	 *
	 * @return array
	 */
	public function getSources()
	{
		$sources = array();

		foreach ($this->component->getSources() as $key => $source)
		{
			$sources[$key] = $source['label'];
		}

		return $sources;
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

			if (isset($sources[$source]))
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
