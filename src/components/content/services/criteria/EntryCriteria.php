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
	public $status = 'live';
	public $archived = false;
	public $order = 'dateCreated desc';
	public $offset;
	public $limit = 100;
}
