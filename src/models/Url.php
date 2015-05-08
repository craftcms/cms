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
 * URL model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Url extends Model
{
	// Properties
	// =========================================================================

	/**
	 * @var string URL
	 */
	public $url;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'url' => Craft::t('app', 'URL'),
		];
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['url'], 'required'],
			[['url'], 'url'],
		];
	}
}
