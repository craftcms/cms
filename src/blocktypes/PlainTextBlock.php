<?php
namespace Blocks;

class PlainTextBlock extends BaseBlock
{
	public $name = 'Plain Text';

	public $settings = array(
		'multiine' => false,
		'maxLength' => null
	);

	protected $settingsTemplate = '_blocktypes/PlainText/settings';
}
