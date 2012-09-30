<?php
namespace Blocks;

/**
 * Base block model class
 *
 * Used for transporting block data throughout the system.
 *
 * @abstract
 */
abstract class BaseBlockModel extends BaseComponentModel
{
	public function defineAttributes()
	{
		$attributes = parent::defineAttributes();
		$attributes['name'] = AttributeType::String;
		$attributes['handle'] = AttributeType::String;
		$attributes['instructions'] = AttributeType::String;
		$attributes['required'] = AttributeType::Bool;

		if (Blocks::hasPackage(BlocksPackage::Language))
		{
			$attributes['translatable'] = AttributeType::Bool;
		}

		return $attributes;
	}
}
