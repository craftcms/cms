<?php
namespace craft\app\assetsourcetypes;

use Craft;
use craft\app\enums\AttributeType;
use craft\app\io\flysystemadapters\Local as LocalAdapter;

/**
 * The local asset source type class. Handles the implementation of the local filesystem as an asset source type in
 * Craft.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright  Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license    http://buildwithcraft.com/license Craft License Agreement
 * @see        http://buildwithcraft.com
 * @package    craft.app.assetsourcetypes
 * @since      1.0
 */
class Local extends BaseAssetSourceType
{
	// Properties
	// =========================================================================

	/**
	 * Whether this is a local source or not. Defaults to false.
	 *
	 * @var bool
	 */
	protected $isSourceLocal = true;


	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IComponentType::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('app', 'Local Folder');
	}

	/**
	 * @inheritDoc ISavableComponentType::getSettingsHtml()
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return Craft::$app->templates->render('_components/assetsourcetypes/Local/settings', array(
			'settings' => $this->getSettings(),
		));
	}

	/**
	 * @inheritDoc ISavableComponentType::prepSettings()
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function prepSettings($settings)
	{
		// Add a trailing slash to the Path and URL settings
		$settings['path'] = !empty($settings['path']) ? rtrim($settings['path'], '/') : '';

		return $settings;
	}

	/**
	 * @inheritDoc IAssetSourceType::isLocal()
	 *
	 * @return bool
	 */
	public function isLocal()
	{
		return true;
	}

	/**
	 * Return a path where the image sources are being stored for this source.
	 *
	 * @return string
	 */
	public function getImageTransformSourceLocation()
	{
		return $this->getSettings()->path;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseSavableComponentType::defineSettings()
	 *
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'path' => array(AttributeType::String, 'required' => true),
		);
	}

	/**
	 * @inheritDoc BaseFlysystemFileSourceType::createAdapter()
	 *
	 * @return LocalAdapter
	 */
	protected function createAdapter()
	{
		return new LocalAdapter($this->getSettings()->path);
	}
}
