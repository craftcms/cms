<?php
namespace Blocks;

class PlainTextBlock extends BaseBlock
{
	public $name = 'Plain Text';

	protected $settings = array(
		'multiline' => false,
		'maxLength' => null
	);

	protected $settingsTemplate = '_blocktypes/PlainText/settings';
}
