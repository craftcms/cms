<?php
namespace Blocks;

/**
 *
 */
class CheckboxesBlock extends BaseOptionsBlock
{
	/**
	 * Returns the type of block this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Blocks::t('Checkboxes');
	}

	/**
	 * Returns the label for the Options setting.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getOptionsSettingsLabel()
	{
		return Blocks::t('Checkbox Options');
	}

	/**
	 * Returns the block's input HTML.
	 *
	 * @return string
	 */
	public function getInputHtml()
	{
		return TemplateHelper::render('_components/blocks/Checkboxes/field', array(
			'settings' => new ModelVariable($this->getSettings())
		));
	}
}
