<?php
namespace Blocks;

/**
 * S3 source type class
 */
class S3AssetSource extends BaseAssetSource
{
	/**
	 * Returns the name of the source type.
	 *
	 * @return string
	 */
	public function getName()
	{
		return 'Amazon S3';
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
			'keyId'  => array(AttributeType::String, 'required' => true),
			'secret' => array(AttributeType::String, 'required' => true),
		);
	}

	/**
	 * Returns the component's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return blx()->templates->render('_components/assetsources/S3/settings', array(
			'settings' => $this->getSettings()
		));
	}
}
