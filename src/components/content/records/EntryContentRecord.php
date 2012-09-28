<?php
namespace Blocks;

/**
 *
 */
class EntryContentRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'entrycontent';
	}

	public function defineAttributes()
	{
		$attributes = array();

		$blocks = blx()->entryBlocks->getAllBlocks();
		foreach ($blocks as $block)
		{
			$blockType = blx()->blockTypes->populateBlockType($block);
			$attribute = $blockType->defineContentAttribute();
			$attribute['label'] = $block->name;

			// Required?
			if ($block->required)
			{
				$attribute['required'] = true;
			}

			$attributes[$block->handle] = $attribute;
		}

		return $attributes;
	}

	public function defineRelations()
	{
		return array(
			'entry' => array(static::BELONGS_TO, 'EntryRecord', 'required' => true),
		);
	}
}
