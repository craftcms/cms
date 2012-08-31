<?php
namespace Blocks;

/**
 *
 */
class PlainTextBlock extends BaseBlock
{
	protected $settingsTemplate = '_blocktypes/PlainText/settings';
	protected $fieldTemplate = '_blocktypes/PlainText/field';

	public function getType()
	{
		return Blocks::t('Plain Text');
	}

	protected function getDefaultSettings()
	{
		return array(
			'multiline'     => true,
			'hint'          => 'Enter text…',
			'maxLength'     => null,
			'maxLengthUnit' => 'words'
		);
	}
}
