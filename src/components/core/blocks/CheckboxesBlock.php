<?php
namespace Blocks;

/**
 *
 */
class CheckboxesBlock extends BaseOptionsBlock
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
	 * @param string $handle
	 * @param mixed  $values
	 * @return string
	 */
	public function getInputHtml($handle, $values)
	{
		return blx()->templates->render('_components/blocks/Checkboxes/input', array(
			'handle'   => $handle,
			'values'   => $values,
			'settings' => $this->getSettings()
		));
	}
}
