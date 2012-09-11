<?php
namespace Blocks;

/**
 *
 */
class NumberBlock extends BaseBlock
{
	/**
	 * Returns the type of block this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Blocks::t('Number');
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
			'min'      => array(AttributeType::Number, 'default' => 0),
			'max'      => array(AttributeType::Number, 'compare' => '>= min'),
			'decimals' => array(AttributeType::Number, 'default' => 0),
		);
	}

	/**
	 * Returns the block's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return TemplateHelper::render('_components/blocks/Number/settings', array(
			'settings' => new ModelVariable($this->getSettings())
		));
	}

	/**
	 * Returns the content column type.
	 *
	 * @return string
	 */
	public function defineContentColumn()
	{
		return DbHelper::getNumberColumnConfig($this->settings->min, $this->settings->max, $this->settings->decimals);
	}

	/**
	 * Returns the block's input HTML.
	 *
	 * @return string
	 */
	public function getInputHtml()
	{
		return TemplateHelper::render('_components/blocks/Number/field', array(
			'settings' => new ModelVariable($this->getSettings())
		));
	}
}
