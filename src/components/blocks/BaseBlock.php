<?php
namespace Blocks;

/**
 * Block base class
 */
abstract class BaseBlock extends BaseComponent implements IBlock
{
	protected $componentType = 'Block';
	protected $settingsColumn = 'blockSettings';

	/**
	 * Returns the content column type.
	 *
	 * @return string
	 */
	public function defineContentColumn()
	{
		// Default to a varchar column
		return ColumnType::Varchar;
	}

	/**
	 * Returns the block's input HTML.
	 *
	 * @abstract
	 * @return string
	 */
	abstract public function getBlockHtml();
}
