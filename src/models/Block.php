<?php
namespace Blocks;

/**
 *
 */
class Block extends BaseModel
{
	public function getTableName()
	{
		return 'blocks';
	}

	protected function getProperties()
	{
		return array(
			'name'         => PropertyType::Name,
			'handle'       => array(PropertyType::Handle, 'reservedWords' => 'id,date_created,date_updated,uid,title'),
			'class'        => PropertyType::ClassName,
			'instructions' => PropertyType::Text,
			'required'     => PropertyType::Boolean,
			'sort_order'   => PropertyType::SortOrder,
			'settings'     => PropertyType::Text,
		);
	}
}
