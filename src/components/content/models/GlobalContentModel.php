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
		$attributes['id'] = AttributeType::Number;

		if (Blocks::hasPackage(BlocksPackage::Language))
		{
			$attributes['language'] = AttributeType::Language;
		}

		return $attributes;
	}
}
