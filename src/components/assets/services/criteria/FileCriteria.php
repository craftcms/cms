<?php
namespace Blocks;

/**
 * Folders parameters
 */
class FileCriteria extends BaseCriteria
{
	public $id;
	public $sourceId;
	public $folderId;
	public $filename;
	public $kind;
	public $order = 'filename asc';
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
		return blx()->assets->findFiles($this);
	}

	/**
	 * Returns the first entry that matches the criteria.
	 *
	 * @access protected
	 * @return EntryModel|null
	 */
	protected function findFirstEntity()
	{
		return blx()->assets->findFile($this);
	}

	/**
	 * Returns the total entries that match the criteria.
	 *
	 * @access protected
	 * @return int
	 */
	protected function getTotalEntities()
	{
		return blx()->assets->getTotalFiles($this);
	}
}
