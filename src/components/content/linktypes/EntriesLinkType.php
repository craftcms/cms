<?php
namespace Blocks;

/**
 * Entries link type class
 */
class EntriesLinkType extends BaseLinkType
{
	/**
	 * Returns the type of links this creates.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Blocks::t('Entries');
	}

	/**
	 * Defines any link type-specific settings.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineSettings()
	{
		$settings = array();

		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$settings['sections'] = array(AttributeType::Mixed);
		}

		return $settings;
	}

	/**
	 * Returns the link's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return blx()->templates->render('_components/linktypes/Entries/settings', array(
			'settings' => $this->getSettings()
		));
	}
}
