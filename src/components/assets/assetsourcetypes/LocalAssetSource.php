<?php
namespace Blocks;

/**
 * Local source type class
 */
class LocalAssetSource extends BaseAssetSource
{
	/**
	 * Returns the name of the source type.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Blocks::t('Local Folder');
	}

	/**
	 * Defines the settings.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'path' => array(AttributeType::String, 'required' => true),
			'url'  => array(AttributeType::String, 'required' => true),
		);
	}

	/**
	 * Returns the component's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return blx()->templates->render('_components/assetsources/Local/settings', array(
			'settings' => $this->getSettings()
		));
	}
}
