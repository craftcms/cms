<?php
namespace Blocks;

/**
 * Page block model class.
 */
class PageBlockModel extends BaseBlockModel
{
	/**
	 * @return mixed
	 */
	public function defineAttributes()
	{
		$attributes = parent::defineAttributes();
		$attributes['pageId'] = AttributeType::Number;

		return $attributes;
	}
}
