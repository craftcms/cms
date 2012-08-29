<?php
namespace Blocks;

/**
 *
 */
class UserBlock extends BaseBlockModel
{
	public function getTableName()
	{
		return 'userblocks';
	}

	protected function getRelations()
	{
		return array(
			'user' => array(static::BELONGS_TO, 'User'),
		);
	}

}
