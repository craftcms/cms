<?php
namespace Blocks;

/**
 *
 */
class PlainTextBlock extends BaseBlock
{
	protected $settingsTemplate = '_components/blocks/PlainText/settings';
	protected $fieldTemplate = '_components/blocks/PlainText/field';

	/**
	 * @return string|void
	 */
	public function getType()
	{
		return Blocks::t('Plain Text');
	}

	/**
	 * @return array
	 */
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
