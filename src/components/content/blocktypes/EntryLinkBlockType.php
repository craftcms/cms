<?php
namespace Blocks;

/**
 * Entry Link block type class
 */
class EntryLinkBlockType extends BaseBlock
{
	/**
	 * Returns the type of block this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Blocks::t('Plain Text');
	}

	/**
	 * Defines the settings.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineSettings()
	{
		$settings = array();

		if (Blocks::hasPackage(Blocks::PublishPro))
		{
			$settings['sections'] = array(AttributeType::Mixed);
		}

		return $settings;
	}

	/**
	 * Returns the block's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return blx()->templates->render('_components/blocks/EntryLink/settings', array(
			'settings' => $this->getSettings()
		));
	}

	/**
	 * Returns the block's input HTML.
	 *
	 * @param string $name
	 * @param mixed  $value
	 * @return string
	 */
	public function getInputHtml($name, $value)
	{
		return blx()->templates->render('_components/blocks/EntryLink/input', array(
			'name'     => $name,
			'value'    => $value,
			'settings' => $this->getSettings()
		));
	}
}
