<?php
namespace Blocks;

/**
 *
 */
class UserProfileRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'userprofiles';
	}

	public function defineAttributes()
	{
		$attributes = array(
			'firstName' => array(AttributeType::String, 'maxLength' => 100),
			'lastName'  => array(AttributeType::String, 'maxLength' => 100),
		);

		$blocks = blx()->userProfileBlocks->getAllBlocks();
		foreach ($blocks as $block)
		{
			$blockType = blx()->blockTypes->populateBlockType($block);
			$attribute = $blockType->defineContentAttribute();
			$attribute['label'] = $block->name;

			$attributes[$block->handle] = $attribute;
		}

		return $attributes;
	}

	public function defineRelations()
	{
		return array(
			'user' => array(static::BELONGS_TO, 'UserRecord', 'unique' => true, 'required' => true),
		);
	}
}
