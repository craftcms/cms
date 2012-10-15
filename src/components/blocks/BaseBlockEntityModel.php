<?php
namespace Blocks;

/**
 * Block entity model base class
 */
abstract class BaseBlockEntityModel extends BaseModel
{
	private $_blocks;
	private $_content;

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
			$content = $this->_getContent();
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
	 * @acess private
	 * @access private
	 * @return array
	 */
	private function _getBlocks()
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
	 * @acess private
	 * @access private
	 * @return array
	 */
	private function _getContent()
	{
		if (!isset($this->_content))
		{
			$this->_content = $this->getContent();

			if ($this->_content instanceof \CModel)
			{
				$this->_content = $this->_content->getAttributes();
			}
		}

		return $this->_content;
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
}
