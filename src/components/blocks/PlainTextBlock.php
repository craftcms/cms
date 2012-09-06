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
			'multiline'     => array(AttributeType::Boolean, 'default' => true),
			'hint'          => array(AttributeType::Varchar, 'default' => Blocks::t('Enter text…', null, null, null, blx()->language)),
			'maxLength'     => array(AttributeType::Int),
			'maxLengthUnit' => array(AttributeType::Enum, 'options' => array('words', 'chars')),
		);
	}
}
