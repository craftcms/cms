<?php
namespace Blocks;

/**
 *
 */
class DropdownBlock extends BaseOptionsBlock
{
	/**
	 * Returns the type of block this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Blocks::t('Dropdown');
	}

	/**
	 * Returns the label for the Options setting.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getOptionsSettingsLabel()
	{
		return Blocks::t('Dropdown Options');
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
		return TemplateHelper::render('_components/blocks/Dropdown/input', array(
			'handle'   => $handle,
			'value'    => $value,
			'settings' => $this->getSettings()
		));
	}
}
