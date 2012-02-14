<?php
namespace Blocks;

class DropdownBlock extends BaseOptionsBlock
{
	public $name = 'Dropdown';

	protected $settingsTemplate = '_blocktypes/Dropdown/settings';
	protected $columnType = AttributeType::Varchar;
}
