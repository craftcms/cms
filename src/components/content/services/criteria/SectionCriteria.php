<?php
namespace Blocks;

/**
 * Section criteria class
 */
class SectionCriteria extends BaseCriteria
{
	public $id;
	public $handle;
	public $hasUrls = false;
	public $order = 'name asc';
	public $offset;
	public $limit;
	public $indexBy;

	/**
	 * Returns all sections that match the criteria.
	 *
	 * @param array|null $criteria
	 * @return array
	 */
	public function find($criteria = null)
	{
		$this->setCriteria($criteria);
		return blx()->sections->findSections($this);
	}

	/**
	 * Returns the first section that matches the criteria.
	 *
	 * @param array|null $criteria
	 * @return SectionModel|null
	 */
	public function first($criteria = null)
	{
		$this->setCriteria($criteria);
		return blx()->sections->findSection($this);
	}

	/**
	 * Returns the total sections that match the criteria.
	 *
	 * @param array|null $criteria
	 * @return int
	 */
	public function total($criteria = null)
	{
		$this->setCriteria($criteria);
		return blx()->sections->getTotalSections($this);
	}
}
