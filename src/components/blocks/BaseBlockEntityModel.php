<?php
namespace Blocks;

/**
 * Block entity model base class
 */
abstract class BaseBlockEntityModel extends BaseModel
{
	private $_blockValuesByHandle;
	private $_blockValuesById;

	/**
	 * Is set?
	 *
	 * @param $name
	 * @return bool
	 */
	function __isset($name)
	{
		if (isset($this->_blockValuesByHandle) && array_key_exists($name, $this->_blockValuesByHandle))
		{
			return true;
		}
		else
		{
			return parent::__isset($name);
		}
	}

	/**
	 * Getter
	 *
	 * @param string $name
	 * @return mixed
	 */
	function __get($name)
	{
		if (isset($this->_blockValuesByHandle) && array_key_exists($name, $this->_blockValuesByHandle))
		{
			return $this->_blockValuesByHandle[$name];
		}
		else
		{
			return parent::__get($name);
		}
	}

	/**
	 * Gets a block value by its ID.
	 *
	 * @param int $id
	 * @return mixed
	 */
	public function getBlockValueById($id)
	{
		if (isset($this->_blockValuesById[$id]))
		{
			return $this->_blockValuesById[$id];
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
		if (is_array($values))
		{
			foreach ($values as $id => $value)
			{
				if (is_string($id) && strncmp($id, 'block', 5) == 0)
				{
					$id = substr($id, 5);
				}

				$this->_blockValuesById[$id] = $value;
			}
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
		$this->_blockValuesById = array();

		if ($attributes instanceof \CModel)
		{
			$attributes = $attributes->getAttributes();
		}

		foreach ($blocks as $block)
		{
			if (isset($attributes[$block->$indexedBy]))
			{
				$value = $attributes[$block->$indexedBy];
			}
			else
			{
				$value = null;
			}

			$this->_blockValuesByHandle[$block->handle] = $value;
			$this->_blockValuesById[$block->id] = $value;
		}
	}
}
