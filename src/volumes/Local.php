<?php
namespace craft\app\volumes;

use Craft;
use craft\app\base\Volume;
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
 * @package    craft.app.volumes
 * @since      1.0
 */
class Local extends Volume
{
	// Static
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function displayName()
	{
		return Craft::t('app', 'Local Folder');
	}

	/**
	 * @inheritdoc
	 */
	public static function isLocal()
	{
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public static function populateModel($model, $config)
	{
		if (isset($config['path']))
		{
			$config['path'] = rtrim($config['path'], '/');
		}

		parent::populateModel($model, $config);
	}

	// Properties
	// =========================================================================

	/**
	 * Path to the root of this sources local folder.
	 *
	 * @var string
	 */
	public $path = "";

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ISavableComponentType::getSettingsHtml()
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return Craft::$app->templates->render('_components/volumes/Local/settings', array(
			'volume' => $this,
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
		// Remove the trailing slash to the Path and URL settings
		$settings['path'] = !empty($settings['path']) ? rtrim($settings['path'], '/') : '';

		return $settings;
	}

	/**
	 * @inheritdoc
	 */
	public function getRootPath()
	{
		return $this->path;
	}

	/**
	 * @inheritdoc
	 */
	public function getRootUrl()
	{
		return rtrim($this->url, '/').'/';
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
