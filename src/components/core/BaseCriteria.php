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
	function __construct($criteria = null)
	{
		$this->setCriteria($criteria);
	}

	/**
	 * Magic __call() method, used for chain-setting criteria properties.
	 *
	 * @param string $name
	 * @param array $arguments
	 */
	function __call($name, $arguments)
	{
		if (property_exists($this, $name))
		{
			if (count($arguments) == 1)
			{
				$this->$name = $arguments[0];
			}
			else
			{
				$this->$name = $arguments;
			}
		}

		return $this;
	}

	/**
	 * Sets the criteria based on an array’s keys and values.
	 *
	 * @param array|null $criteria
	 */
	public function setCriteria($criteria = null)
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
