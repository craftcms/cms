<?php
namespace Blocks;

/**
 *
 */
class UserContent extends BaseModel
{
	public function getTableName()
	{
		return 'usercontent';
	}

	protected function getRelations()
	{
		return array(
			'user' => array(static::BELONGS_TO, 'User', 'unique' => true, 'required' => true),
		);
	}
}
