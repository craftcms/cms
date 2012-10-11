<?php
namespace Blocks;

/**
 * Global content model class
 *
 * Used for transporting entry data throughout the system.
 */
class GlobalContentModel extends BaseBlockEntityModel
{
	public function defineAttributes()
	{
		return array(
			'id'       => AttributeType::Number,
			'language' => AttributeType::Language,
		);
	}
}
