<?php
namespace Blocks;

/**
 * Entry parameters
 */
class EntryParams extends BaseParams
{
	public $id;
	public $slug;
	/* BLOCKSPRO ONLY */
	public $language;
	/* end BLOCKSPRO ONLY */
	public $order = 'title asc';
	public $offset;
	public $limit;

	/**
	 * Constructor
	 *
	 * @param array|null $params
	 */
	public function __construct($params = array())
	{
		$this->language = blx()->language;
		parent::__construct($params);
	}
}
