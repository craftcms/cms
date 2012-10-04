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

	/**
	 * Returns the type of entity these blocks will be attached to.
	 *
	 * @return string
	 */
	public function getEntityType()
	{
		return 'entry';
	}
}
