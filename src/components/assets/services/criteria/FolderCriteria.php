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
	 * Returns all entries that match the criteria.
	 *
	 * @access protected
	 * @return array
	 */
	protected function findEntities()
	{
		return blx()->assets->getFolders($this);
	}

	/**
	 * Returns the first entry that matches the criteria.
	 *
	 * @access protected
	 * @return EntryModel|null
	 */
	protected function findFirstEntity()
	{
		return blx()->assets->getFolder($this);
	}

	/**
	 * Returns the total entries that match the criteria.
	 *
	 * @access protected
	 * @return int
	 */
	protected function getTotalEntities()
	{
		return blx()->assets->getTotalFolders($this);
	}
}
