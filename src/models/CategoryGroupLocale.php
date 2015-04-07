<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\Model;

/**
 * CategoryGroupLocale model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class CategoryGroupLocale extends Model
{
	// Properties
	// =========================================================================

	/**
	 * @var integer ID
	 */
	public $id;

	/**
	 * @var integer Group ID
	 */
	public $groupId;

	/**
	 * @var string Locale
	 */
	public $locale;

	/**
	 * @var string URL format
	 */
	public $urlFormat;

	/**
	 * @var string Nested URL format
	 */
	public $nestedUrlFormat;


	/**
	 * @var bool
	 */
	public $urlFormatIsRequired = false;

	/**
	 * @var bool
	 */
	public $nestedUrlFormatIsRequired = false;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'urlFormat' => Craft::t('app', 'URL Format'),
			'nestedUrlFormat' => Craft::t('app', 'URL Format'),
		];
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		$rules = [
			[['id'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['groupId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['locale'], 'craft\\app\\validators\\Locale'],
			[['urlFormat', 'nestedUrlFormat'], 'craft\\app\\validators\\UrlFormat'],
			[['id', 'groupId', 'locale', 'urlFormat', 'nestedUrlFormat'], 'safe', 'on' => 'search'],
		];

		if ($this->urlFormatIsRequired)
		{
			$rules[] = [['urlFormat'], 'required'];
		}

		if ($this->nestedUrlFormatIsRequired)
		{
			$rules[] = [['nestedUrlFormat'], 'required'];
		}

		return $rules;
	}
}
