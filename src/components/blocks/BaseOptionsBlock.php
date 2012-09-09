<?php
namespace Blocks;

/**
 *
 */
abstract class BaseOptionsBlock extends BaseBlock
{
	/**
	 * Defines the block settings.
	 *
	 * @return array
	 */
	public function defineSettings()
	{
		return array(
			'options' => AttributeType::Mixed
		);
	}

	/**
	 * Returns the block's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return TemplateHelper::render('_components/blocks/optionsblocksettings', array(
			'label'    => $this->getOptionsSettingsLabel(),
			'settings' => $this->getSettings()
		));
	}

	/**
	 * Returns the label for the Options setting.
	 *
	 * @abstract
	 * @access protected
	 * @return string
	 */
	abstract protected function getOptionsSettingsLabel();
}
