<?php
namespace Blocks;

class MultiselectBlock extends BaseOptionsBlock
{
	public $blocktypeName = 'Multiselect';

	protected $settingsTemplate = '_blocktypes/Multiselect/settings';
	protected $fieldTemplate = '_blocktypes/Multiselect/field';
}
