<?php
namespace Blocks;

/**
 * Entry criteria class
 */
class EntryCriteria extends BaseCriteria
{
	public $id;
	public $slug;
	public $uri;
	public $sectionId;
	public $section;
	public $language;
	public $authorId;
	public $authorGroupId;
	public $authorGroup;
	public $after;
	public $before;
	public $status = 'live';
	public $archived = false;
	public $order = 'postDate desc';
	public $offset;
	public $limit = 100;
	public $indexBy;

	/**
	 * Returns all entries that match the criteria.
	 *
	 * @param array|null $criteria
	 * @return array
	 */
	public function find($criteria = null)
	{
		$this->setCriteria($criteria);
		return blx()->entries->findEntries($this);
	}

	/**
	 * Returns the first entry that matches the criteria.
	 *
	 * @param array|null $criteria
	 * @return EntryModel|null
	 */
	public function first($criteria = null)
	{
		$this->setCriteria($criteria);
		return blx()->entries->findEntry($this);
	}

	/**
	 * Returns the total entries that match the criteria.
	 *
	 * @param array|null $criteria
	 * @return int
	 */
	public function total($criteria = null)
	{
		$this->setCriteria($criteria);
		return blx()->entries->getTotalEntries($this);
	}
}
