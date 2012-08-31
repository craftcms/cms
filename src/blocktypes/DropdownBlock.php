<?php
namespace Blocks;

/**
 *
 */
class DropdownBlock extends BaseOptionsBlock
{
	protected $settingsTemplate = '_blocktypes/Dropdown/settings';
	protected $fieldTemplate = '_blocktypes/Dropdown/field';
	protected $columnType = PropertyType::Varchar;

	public function getType()
	{
		return Blocks::t('Dropdown');
	}
}
