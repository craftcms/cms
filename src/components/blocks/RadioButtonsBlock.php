<?php
namespace Blocks;

/**
 *
 */
class RadioButtonsBlock extends BaseOptionsBlock
{
	/**
	 * Returns the type of block this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Blocks::t('Radio Buttons');
	}

	/**
	 * Returns the label for the Options setting.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getOptionsSettingsLabel()
	{
		return Blocks::t('Radio Button Options');
	}

	/**
	 * Returns the block's input HTML.
	 *
	 * @return string
	 */
	public function getInputHtml()
	{
		return TemplateHelper::render('_components/blocks/RadioButtons/field', array(
			'settings' => new ModelVariable($this->getSettings())
		));
	}
}
