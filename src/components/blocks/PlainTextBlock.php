<?php
namespace Blocks;

/**
 *
 */
class PlainTextBlock extends BaseBlock
{
	/**
	 * Returns the type of block this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Blocks::t('Plain Text');
	}

	/**
	 * Defines the settings.
	 *
	 * @return array
	 */
	public function defineSettings()
	{
		return array(
			'multiline'     => array(AttributeType::Bool, 'default' => true),
			'hint'          => array(AttributeType::String, 'default' => Blocks::t('Enter text…', null, null, null, blx()->language)),
			'maxLength'     => array(AttributeType::Number, 'min' => 0),
			'maxLengthUnit' => array(AttributeType::Enum, 'values' => array('words', 'chars')),
		);
	}

	/**
	 * Returns the block's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return TemplateHelper::render('_components/blocks/PlainText/settings', array(
			'settings' => $this->getSettings()
		));
	}

	/**
	 * Returns the block's input HTML.
	 *
	 * @return string
	 */
	public function getBlockHtml()
	{
		return TemplateHelper::render('_components/blocks/PlainText/field', array(
			'settings' => $this->getSettings()
		));
	}
}
