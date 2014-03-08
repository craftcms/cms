<?php
namespace Craft;

/**
 * Class ElementDependency
 */
class ElementsDependency extends \CCacheDependency
{
	private $_elements = null;


	public function __construct(array $elements)
	{
		$this->_elements = $elements;
	}

	/**
	 * @return mixed
	 * @throws Exception
	 */
	protected function generateDependentData()
	{
		if($this->_elements !== null && is_array($this->_elements) && count($this->_elements) > 0)
		{
			$dateUpdatedDates = array();

			foreach ($this->_elements as $element)
			{
				$dateUpdatedDates[] = $element->dateUpdated;
			}

			return $dateUpdatedDates;
		}
		else
		{
			throw new Exception(Craft::t('Missing dependent element.'));
		}
	}
}
