<?php
namespace Blocks;

/**
 *
 */
class NumberBlockType extends BaseBlockType
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
		return blx()->templates->render('_components/blocktypes/Number/settings', array(
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
		return ModelHelper::getNumberAttributeConfig($this->settings->min, $this->settings->max, $this->settings->decimals);
	}

	/**
	 * Returns the block's input HTML.
	 *
	 * @param string $name
	 * @param mixed  $value
	 * @return string
	 */
	public function getInputHtml($name, $value)
	{
		return blx()->templates->render('_components/blocktypes/Number/input', array(
			'name'     => $name,
			'value'    => $value,
			'settings' => $this->getSettings()
		));
	}
}
