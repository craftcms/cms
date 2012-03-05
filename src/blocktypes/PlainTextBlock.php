<?php
namespace Blocks;

class PlainTextBlock extends BaseBlock
{
	public $name = 'Plain Text';

	protected $settings = array(
		'multiline' => true,
		'maxLength' => null
	);

	protected $settingsTemplate = '_blocktypes/PlainText/settings';
	protected $fieldTemplate = '_blocktypes/PlainText/field';
}
