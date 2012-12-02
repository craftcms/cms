<?php
namespace Blocks;

/**
 * Base criteria class
 */
abstract class BaseCriteria
{
	public $offset = 0;
	public $limit = 100;

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

	/**
	 * Returns all entities that match the criteria.
	 *
	 * @param array|null $criteria
	 * @return array
	 */
	public function find($criteria = null)
	{
		$this->setCriteria($criteria);
		$this->findEntities();
	}

	/**
	 * Returns the first entity that matches the criteria.
	 *
	 * @param array|null $criteria
	 * @return EntryModel|null
	 */
	public function first($criteria = null)
	{
		$this->setCriteria($criteria);
		$this->findFirstEntity();
	}

	/**
	 * Returns the total entities that match the criteria.
	 *
	 * @param array|null $criteria
	 * @return int
	 */
	public function total($criteria = null)
	{
		$this->setCriteria($criteria);
		$this->getTotalEntities();
	}

	/**
	 * Returns all entities that match the criteria.
	 *
	 * @abstract
	 * @access protected
	 * @return array
	 */
	abstract protected function findEntities();

	/**
	 * Returns the first entity that matches the criteria.
	 *
	 * @abstract
	 * @access protected
	 * @return EntryModel|null
	 */
	abstract protected function findFirstEntity();

	/**
	 * Returns the total entities that match the criteria.
	 *
	 * @abstract
	 * @access protected
	 * @return int
	 */
	abstract protected function getTotalEntities();
}
