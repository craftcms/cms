<?php
namespace Blocks;

/**
 *
 */
class CheckboxesBlockType extends BaseOptionsBlockType
{
	protected $multi = true;

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
	 * @param string $name
	 * @param mixed  $values
	 * @return string
	 */
	public function getInputHtml($name, $values)
	{
		return blx()->templates->render('_components/blocktypes/Checkboxes/input', array(
			'name'     => $name,
			'values'   => $values,
			'settings' => $this->getSettings()
		));
	}
}
