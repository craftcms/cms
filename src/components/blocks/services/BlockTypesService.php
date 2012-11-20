<?php
namespace Blocks;

/**
 *
 */
class BlockTypesService extends BaseApplicationComponent
{
	/**
	 * Returns all installed block types.
	 *
	 * @return array
	 */
	public function getAllBlockTypes()
	{
		return blx()->components->getComponentsByType(ComponentType::Block);
	}

	/**
	 * Gets a block type.
	 *
	 * @param string $class
	 * @return BaseBlockType|null
	 */
	public function getBlockType($class)
	{
		return blx()->components->getComponentByTypeAndClass(ComponentType::Block, $class);
	}

	/**
	 * Populates a block type by a block model.
	 *
	 * @param BaseBlockModel $block
	 * @param BaseModel|null $entity
	 * @return BaseBlockType|null
	 */
	public function populateBlockType(BaseBlockModel $block, $entity = null)
	{
		$blockType = blx()->components->populateComponentByTypeAndModel(ComponentType::Block, $block);
		if ($blockType)
		{
			$blockType->entity = $entity;
			return $blockType;
		}
	}
}
