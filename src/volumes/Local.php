<?php
namespace craft\app\volumes;

use Craft;
use craft\app\base\Volume;
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
	public function rules()
	{
		$rules = parent::rules();
		$rules[] = [['path'], 'required'];
		return $rules;
	}

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
	 * @inheritdoc
	 */
	public function getSettingsHtml()
	{
		return Craft::$app->getView()->renderTemplate('_components/volumes/Local/settings', array(
			'volume' => $this,
		));
	}

	/**
	 * @inheritdoc
	 */
	public function getRootPath()
	{
		return Craft::$app->getConfig()->parseEnvironmentString($this->path);
	}

	/**
	 * @inheritdoc
	 */
	public function getRootUrl()
	{
		return rtrim(Craft::$app->getConfig()->parseEnvironmentString($this->url), '/').'/';
	}


	// Protected Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 * @return LocalAdapter
	 */
	protected function createAdapter()
	{
		return new LocalAdapter($this->getRootPath());
	}
}
