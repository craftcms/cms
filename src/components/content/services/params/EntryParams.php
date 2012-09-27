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
	public $sectionId;
	public $section;
	public $language;
	/* end BLOCKSPRO ONLY */
	public $status = 'live';
	public $archived = false;
	public $order = 'title asc';
	public $offset;
	public $limit;
}
