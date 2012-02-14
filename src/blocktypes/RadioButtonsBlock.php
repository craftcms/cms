<?php
namespace Blocks;

class RadioButtonsBlock extends BaseOptionsBlock
{
	public $name = 'Radio Buttons';

	protected $settingsTemplate = '_blocktypes/RadioButtons/settings';
	protected $columnType = AttributeType::Varchar;
}
