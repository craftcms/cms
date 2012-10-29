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
}
