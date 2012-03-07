<?php
namespace Blocks;

class PlainTextBlocktype extends BaseBlocktype
{
	public $blocktypeName = 'Plain Text';

	protected $defaultSettings = array(
		'multiline' => true,
		'maxLength' => null
	);

	protected $settingsTemplate = '_blocktypes/PlainText/settings';
	protected $fieldTemplate = '_blocktypes/PlainText/field';
}
