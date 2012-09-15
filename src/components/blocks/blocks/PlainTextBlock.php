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
		return blx()->templates->render('_components/blocks/PlainText/settings', array(
			'settings' => $this->getSettings()
		));
	}

	/**
	 * Returns the content attribute config.
	 *
	 * @return string|array
	 */
	public function defineContentAttribute()
	{
		if ($this->getSettings()->multiline)
			return array(AttributeType::String, 'column' => ColumnType::Text);
		else
			return array(AttributeType::String, 'column' => ColumnType::Varhcar);
	}

	/**
	 * Returns the block's input HTML.
	 *
	 * @param string     $handle
	 * @param mixed      $value
	 * @param array|null $errors
	 * @return string
	 */
	public function getInputHtml($handle, $value, $errors = null)
	{
		return blx()->templates->render('_components/blocks/PlainText/input', array(
			'handle'   => $handle,
			'value'    => $value,
			'errors'   => $errors,
			'settings' => $this->getSettings()
		));
	}
}
