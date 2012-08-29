<?php
namespace Blocks;

/**
 *
 */
class RadioButtonsBlock extends BaseOptionsBlock
{
	public $blocktypeName = 'Radio Buttons';

	protected $settingsTemplate = '_blocktypes/RadioButtons/settings';
	protected $fieldTemplate = '_blocktypes/RadioButtons/field';
	protected $columnType = PropertyType::Varchar;
}
