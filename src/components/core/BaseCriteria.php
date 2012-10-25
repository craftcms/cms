<?php
namespace Blocks;

/**
 * Base criteria class
 */
abstract class BaseCriteria
{
	/**
	 * Constructor
	 *
	 * @param array|null $criteria
	 */
	public function __construct($criteria = array())
	{
		$this->setParams($criteria);
	}

	/**
	 * Sets the params based on an array’s keys and values.
	 *
	 * @param array|null $criteria
	 */
	public function setParams($criteria = null)
	{
		if (is_array($criteria))
		{
			foreach ($criteria as $name => $value)
			{
				if (property_exists($this, $name))
				{
					$this->$name = $value;
				}
			}
		}
	}
}
