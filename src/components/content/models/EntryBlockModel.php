<?php
namespace Blocks;

/**
 * Entry block model class.
 */
class EntryBlockModel extends BaseBlockModel
{
	/**
	 * @return mixed
	 */
	public function defineAttributes()
	{
		$attributes = parent::defineAttributes();

		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$attributes['sectionId'] = AttributeType::Number;
		}

		return $attributes;
	}
}
