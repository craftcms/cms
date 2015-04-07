<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\base\Model;

/**
 * Validates the required Site attributes for the installer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class SiteSettings extends Model
{
	// Properties
	// =========================================================================

	/**
	 * @var string Site name
	 */
	public $siteName;

	/**
	 * @var string Site URL
	 */
	public $siteUrl;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['siteName', 'siteUrl'], 'required'],
			[['siteUrl'], 'craft\\app\\validators\\Url', 'defaultScheme' => 'http'],
			[['siteName', 'siteUrl'], 'string', 'max' => 255],
			[['siteName', 'siteUrl'], 'safe', 'on' => 'search'],
		];
	}
}
