<?php
namespace Blocks;

class PlainTextBlock extends Block
{
	public $blocktypeName = 'Plain Text';

	protected $defaultSettings = array(
		'multiline' => true,
		'maxLength' => null
	);

	protected $settingsTemplate = '_blocktypes/PlainText/settings';
	protected $fieldTemplate = '_blocktypes/PlainText/field';
}
