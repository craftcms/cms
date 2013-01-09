<?php
namespace Blocks;

/**
 * Content record base class
 */
abstract class BaseEntityRecord extends BaseRecord
{
	/**
	 * Returns the list of blocks associated with this content.
	 *
	 * @abstract
	 * @access protected
	 * @return array
	 */
	abstract protected function getBlocks();

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		$attributes = array();

		foreach ($this->getBlocks() as $block)
		{
			$blockType = blx()->blockTypes->populateBlockType($block);

			if (!empty($blockType))
			{
				$attribute = $blockType->defineContentAttribute();

				if ($attribute !== false)
				{
					$attribute = ModelHelper::normalizeAttributeConfig($attribute);
					$attribute['label'] = $block->name;

					if ($block->required)
					{
						$attribute['required'] = true;
					}

					$attributes[$block->handle] = $attribute;
				}
			}
		}

		return $attributes;
	}
}
