<?php
namespace Blocks;

class RadioButtonsBlocktype extends BaseOptionsBlocktype
{
	public $blocktypeName = 'Radio Buttons';

	protected $settingsTemplate = '_blocktypes/RadioButtons/settings';
	protected $fieldTemplate = '_blocktypes/RadioButtons/field';
	protected $columnType = AttributeType::Varchar;
}
