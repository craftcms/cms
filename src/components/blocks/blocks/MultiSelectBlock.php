<?php
namespace Blocks;

/**
 *
 */
class MultiSelectBlock extends BaseOptionsBlock
{
	protected $multi = true;

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
	 * @param string $handle
	 * @param mixed  $values
	 * @return string
	 */
	public function getInputHtml($handle, $values)
	{
		return TemplateHelper::render('_components/blocks/MultiSelect/input', array(
			'handle'   => $handle,
			'values'   => $values,
			'settings' => $this->getSettings()
		));
	}
}
