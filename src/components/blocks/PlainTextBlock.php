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
	public function getName()
	{
		return Blocks::t('Plain Text');
	}

	/**
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'multiline'     => array(AttributeType::Bool, 'default' => true),
			'hint'          => array(AttributeType::String, 'default' => Blocks::t('Enter text…', null, null, null, blx()->language)),
			'maxLength'     => array(AttributeType::Number, 'min' => 0),
			'maxLengthUnit' => array(AttributeType::Enum, 'options' => array('words', 'chars')),
		);
	}
}
