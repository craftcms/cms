<?php
namespace Blocks;

/**
 * Block entity model base class
 */
abstract class BaseEntityModel extends BaseModel
{
	private $_blocks;
	private $_content;
	private $_preppedContent;

	/**
	 * Is set?
	 *
	 * @param $name
	 * @return bool
	 */
	function __isset($name)
	{
		$blocks = $this->_getBlocks();
		if (isset($blocks[$name]))
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
		// Is $name a block handle?
		$blocks = $this->_getBlocks();
		if (isset($blocks[$name]))
		{
			if (!isset($this->_preppedContent) || !array_key_exists($name, $this->_preppedContent))
			{
				$content = $this->_getContent();
				if (isset($content[$name]))
				{
					$value = $content[$name];
				}
				else
				{
					$value = null;
				}

				$blockType = blx()->blockTypes->populateBlockType($blocks[$name], $this);
				$this->_preppedContent[$name] = $blockType->prepValue($value);
			}

			return $this->_preppedContent[$name];
		}
		else
		{
			return parent::__get($name);
		}
	}

	/**
	 * Gets the blocks.
	 *
	 * @abstract
	 * @access protected
	 * @return array
	 */
	abstract protected function getBlocks();

	/**
	 * Gets the content.
	 *
	 * @abstract
	 * @access protected
	 * @return array
	 */
	abstract protected function getContent();

	/**
	 * @access protected
	 * @return array
	 */
	protected function _getBlocks()
	{
		if (!isset($this->_blocks))
		{
			$this->_blocks = array();
			foreach ($this->getBlocks() as $block)
			{
				$this->_blocks[$block->handle] = $block;
			}
		}

		return $this->_blocks;
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function _getContent()
	{
		if (!isset($this->_content))
		{
			$content = $this->getContent();

			if ($content instanceof \CModel)
			{
				$content = $content->getAttributes();
			}

			if (!is_array($content))
			{
				$content = array();
			}

			$this->_content = $content;
		}

		return $this->_content;
	}

	/**
	 * Sets content that's indexed by the block ID.
	 *
	 * @param array $content
	 */
	public function setContentIndexedByBlockId($content)
	{
		$this->_content = array();

		$blocksById = array();
		foreach ($this->_getBlocks() as $block)
		{
			$blocksById[$block->id] = $block;
		}

		foreach ($content as $blockId => $value)
		{
			if (isset($blocksById[$blockId]))
			{
				$block = $blocksById[$blockId];
				$this->_content[$block->handle] = $value;
			}
		}
	}

	/**
	 * Sets the content.
	 *
	 * @param array $content
	 */
	public function setContent($content)
	{
		if ($content instanceof \CModel)
		{
			$content = $content->getAttributes();
		}

		$this->_content = $content;
	}

	/**
	 * Returns the raw content saved on this entity.
	 *
	 * @param string|null $name
	 * @return mixed
	 */
	public function getRawContent($name = null)
	{
		$content = $this->_getContent();

		if ($name)
		{
			if (isset($content[$name]))
			{
				return $content[$name];
			}
			else
			{
				return null;
			}
		}
		else
		{
			return $content;
		}
	}
}
