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
	public $limit;
	public $indexBy;

	/**
	 * Returns all sections that match the criteria.
	 *
	 * @access protected
	 * @return array
	 */
	protected function findEntities()
	{
		return blx()->sections->findSections($this);
	}

	/**
	 * Returns the first section that matches the criteria.
	 *
	 * @access protected
	 * @return SectionModel|null
	 */
	protected function findFirstEntity()
	{
		return blx()->sections->findSection($this);
	}

	/**
	 * Returns the total sections that match the criteria.
	 *
	 * @access protected
	 * @return int
	 */
	protected function getTotalEntities()
	{
		return blx()->sections->getTotalSections($this);
	}
}
