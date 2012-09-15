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
	 * @param string $handle
	 * @param mixed  $value
	 * @return string
	 */
	public function getInputHtml($handle, $value)
	{
		return blx()->templates->render('_components/blocks/RadioButtons/input', array(
			'handle'   => $handle,
			'value'    => $value,
			'settings' => $this->getSettings()
		));
	}
}
