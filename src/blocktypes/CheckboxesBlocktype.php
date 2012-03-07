<?php
namespace Blocks;

class CheckboxesBlocktype extends BaseOptionsBlocktype
{
	public $blocktypeName = 'Checkboxes';

	protected $settingsTemplate = '_blocktypes/Checkboxes/settings';
	protected $fieldTemplate = '_blocktypes/Checkboxes/field';
}
