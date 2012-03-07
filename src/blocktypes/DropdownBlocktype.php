<?php
namespace Blocks;

class DropdownBlocktype extends BaseOptionsBlocktype
{
	public $blocktypeName = 'Dropdown';

	protected $settingsTemplate = '_blocktypes/Dropdown/settings';
	protected $columnType = AttributeType::Varchar;
}
