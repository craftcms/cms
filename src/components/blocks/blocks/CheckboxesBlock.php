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
	 * @param mixed $package
	 * @param string $handle
	 * @return string
	 */
	public function getInputHtml($package, $handle)
	{
		return TemplateHelper::render('_components/blocks/Checkboxes/input', array(
			'package'  => $package,
			'handle'   => $handle,
			'settings' => $this->getSettings()
		));
	}
}
