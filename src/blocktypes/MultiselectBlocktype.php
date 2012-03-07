<?php
namespace Blocks;

class MultiselectBlocktype extends BaseOptionsBlocktype
{
	public $blocktypeName = 'Multiselect';

	protected $settingsTemplate = '_blocktypes/Multiselect/settings';
	protected $fieldTemplate = '_blocktypes/Multiselect/field';
}
