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
	public function getInputHtml($data = null)
	{
		return TemplateHelper::render('_components/blocks/Checkboxes/input', array(
			'record'   => new ModelVariable($this->record),
			'settings' => new ModelVariable($this->getSettings()),
			'data'     => $data
		));
	}
}
