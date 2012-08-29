<?php
namespace Blocks;

/**
 *
 */
class DropdownBlock extends BaseOptionsBlock
{
	public $blocktypeName = 'Dropdown';

	protected $settingsTemplate = '_blocktypes/Dropdown/settings';
	protected $fieldTemplate = '_blocktypes/Dropdown/field';
	protected $columnType = PropertyType::Varchar;
}
