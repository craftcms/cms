<?php
namespace Blocks;

/**
 * Section parameters
 */
class SectionParams extends BaseParams
{
	public $id;
	public $handle;
	public $hasUrls = false;
	public $order = 'name asc';
	public $offset;
	public $limit;
}
