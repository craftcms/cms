<?php
namespace Blocks;

/**
 * Folders parameters
 */
class FolderParams extends BaseParams
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
