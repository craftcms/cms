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
	 * @access protected
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'hint'          => array(AttributeType::String, 'default' => Blocks::t('Enter text…', null, null, null, blx()->language)),
			'multiline'     => array(AttributeType::Bool),
			'initialRows'   => array(AttributeType::Number, 'default' => 4),
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
			'settings' => new ModelVariable($this->getSettings())
		));
	}

	/**
	 * Returns the block's input HTML.
	 *
	 * @param mixed $package
	 * @param string $handle
	 * @return string
	 */
	public function getInputHtml($package, $handle)
	{
		return TemplateHelper::render('_components/blocks/PlainText/input', array(
			'package'  => $package,
			'handle'   => $handle,
			'settings' => $this->getSettings()
		));
	}
}
