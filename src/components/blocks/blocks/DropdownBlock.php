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
	 * @param mixed $package
	 * @param string $handle
	 * @return string
	 */
	public function getInputHtml($package, $handle)
	{
		return TemplateHelper::render('_components/blocks/Dropdown/input', array(
			'package'  => $package,
			'handle'   => $handle,
			'settings' => $this->getSettings()
		));
	}
}
