<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\assets;

use Craft;
use yii\web\AssetBundle;

/**
 * Application asset bundle.
 */
class AppAsset extends AssetBundle
{
	/**
	 * @inheritdoc
	 */
	public $sourcePath = '@app/resources';

	/**
	 * @inheritdoc
	 */
	public $depends = [
		'yii\web\JqueryAsset',
	];

	/**
	 * @inheritdoc
	 */
	public $css = [
		'css/craft.css',
	];

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$useCompressedJs = (bool) Craft::$app->getConfig()->get('useCompressedJs');

		// Figure out which Datepicker i18n script to load
		$language = Craft::$app->language;

		if (in_array($language, ['en-GB', 'fr-CA']))
		{
			$datepickerLanguage = $language;
		}
		else
		{
			$languageId = Craft::$app->getLocale()->getLanguageID();

			if (in_array($languageId, ['ar', 'de', 'fr', 'it', 'ja', 'nb', 'nl']))
			{
				$datepickerLanguage = $languageId;
			}
		}

		$this->js = [
			'lib/xregexp-all'.($useCompressedJs ? '-min' : '').'.js',
			'lib/jquery-ui'.($useCompressedJs ? '.min' : '').'.js',
		];

		if (isset($datepickerLanguage))
		{
			$this->js[] = "lib/datepicker-i18n/datepicker-$datepickerLanguage.js";
		}

		$this->js = array_merge($this->js, [
			'lib/velocity'.($useCompressedJs ? '.min' : '').'.js',
			'lib/jquery.placeholder'.($useCompressedJs ? '.min' : '').'.js',
			'lib/fileupload/jquery.ui.widget.js',
			'lib/fileupload/jquery.fileupload.js',
			'lib/garnish-0.1'.($useCompressedJs ? '.min' : '').'.js',
			'js/'.($useCompressedJs ? 'compressed/' : '').'Craft.js',
		]);
	}
}
