<?php
namespace Blocks;

/**
 * Folders parameters
 */
class FileParams extends BaseParams
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
