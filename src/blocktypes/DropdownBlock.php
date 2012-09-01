<?php
namespace Blocks;

/**
 *
 */
class DropdownBlock extends BaseOptionsBlock
{
	protected $settingsTemplate = '_blocktypes/Dropdown/settings';
	protected $fieldTemplate = '_blocktypes/Dropdown/field';

	/**
	 * @return string|void
	 */
	public function getType()
	{
		return Blocks::t('Dropdown');
	}

	/**
	 * Returns the content column type.
	 *
	 * @return string
	 */
	public function getColumnType()
	{
		return PropertyType::Varchar;
	}

}
