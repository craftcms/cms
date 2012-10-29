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
}
