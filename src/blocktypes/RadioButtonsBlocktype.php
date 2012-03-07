<?php
namespace Blocks;

class RadioButtonsBlocktype extends BaseOptionsBlocktype
{
	public $blocktypeName = 'Radio Buttons';

	protected $settingsTemplate = '_blocktypes/RadioButtons/settings';
	protected $columnType = AttributeType::Varchar;
}
