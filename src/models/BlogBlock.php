<?php
namespace Blocks;

/**
 *
 */
class BlogBlock extends BaseBlockModel
{
	public function getTableName()
	{
		return 'blogblocks';
	}

	public function getProperties()
	{
		return array(
			'body' => PropertyType::Text
		);
	}
}
