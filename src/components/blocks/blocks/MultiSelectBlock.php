<?php
namespace Blocks;

/**
 *
 */
class MultiSelectBlock extends BaseOptionsBlock
{
	protected $settingsTemplate = '_components/blocks/MultiSelect/settings';
	protected $fieldTemplate = '_components/blocks/MultiSelect/field';

	/**
	 * Returns the type of block this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Blocks::t('Multi-select');
	}

	/**
	 * Returns the label for the Options setting.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getOptionsSettingsLabel()
	{
		return Blocks::t('Multi-select Options');
	}

	/**
	 * Returns the block's input HTML.
	 *
	 * @return string
	 */
	public function getInputHtml($values = null)
	{
		return TemplateHelper::render('_components/blocks/MultiSelect/input', array(
			'record'   => new ModelVariable($this->record),
			'settings' => new ModelVariable($this->getSettings()),
			'values'   => $values
		));
	}
}
