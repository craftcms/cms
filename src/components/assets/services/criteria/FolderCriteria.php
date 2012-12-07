<?php
namespace Blocks;

/**
 * Folders parameters
 */
class FolderCriteria extends BaseCriteria
{
	public $id;
	public $parentId;
	public $sourceId;
	public $name;
	public $fullPath;
	public $order = 'name asc';
	public $offset;
	public $limit;

	/**
	 * Returns all entities that match the criteria.
	 *
	 * @access protected
	 * @return array
	 */
	protected function findEntities()
	{
	}

	/**
	 * Returns the first entity that matches the criteria.
	 *
	 * @access protected
	 * @return EntryModel|null
	 */
	protected function findFirstEntity()
	{
	}

	/**
	 * Returns the total entities that match the criteria.
	 *
	 * @access protected
	 * @return int
	 */
	protected function getTotalEntities()
	{
	}
}
