<?php
namespace Blocks;

/**
 * Entry parameters
 */
class EntryParams extends BaseParams
{
	public $id;
	public $slug;
	public $uri;
	public $sectionId;
	public $section;
	public $language;
	public $status = 'live';
	public $archived = false;
	public $order = 'dateCreated desc';
	public $offset;
	public $limit;
}
