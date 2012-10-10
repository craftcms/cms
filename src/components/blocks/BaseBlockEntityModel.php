<?php
namespace Blocks;

/**
 * Block entity model base class
 */
abstract class BaseBlockEntityModel extends BaseModel
{
	private $_blockValues;

	/**
	 * Gets a block value by its ID.
	 *
	 * @param int $id
	 * @return mixed
	 */
	public function getBlockValueById($id)
	{
		if (isset($this->_blockValues[$id]))
		{
			return $this->_blockValues[$id];
		}
		else
		{
			return null;
		}
	}

	/**
	 * Sets the block values.
	 *
	 * @param array $values
	 */
	public function setBlockValues($values)
	{
		foreach ($values as $id => $value)
		{
			if (is_string($id) && strncmp($id, 'block', 5) == 0)
			{
				$id = substr($id, 5);
			}

			$this->_blockValues[$id] = $value;
		}
	}

	/**
	 * Sets the block values from an attributes array or model.
	 *
	 * @param array $blocks
	 * @param \CModel|array $attributes
	 */
	public function setBlockValuesFromAttributes($blocks, $attributes, $indexedBy = 'handle')
	{
		$this->_blockValues = array();

		if ($attributes instanceof \CModel)
		{
			$attributes = $attributes->getAttributes();
		}

		foreach ($blocks as $block)
		{
			if (isset($attributes[$block->$indexedBy]))
			{
				$this->_blockValues[$block->id] = $attributes[$block->$indexedBy];
			}
			else
			{
				$this->_blockValues[$block->id] = null;
			}
		}
	}
}
