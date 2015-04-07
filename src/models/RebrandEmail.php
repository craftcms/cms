<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\Model;

Craft::$app->requireEdition(Craft::Client);

/**
 * Email message model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class RebrandEmail extends Model
{
	// Properties
	// =========================================================================

	/**
	 * @var string Key
	 */
	public $key;

	/**
	 * @var string Locale
	 */
	public $locale;

	/**
	 * @var string Subject
	 */
	public $subject;

	/**
	 * @var string Body
	 */
	public $body;

	/**
	 * @var string Html body
	 */
	public $htmlBody;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['locale'], 'craft\\app\\validators\\Locale'],
			[['key', 'locale', 'subject', 'body', 'htmlBody'], 'safe', 'on' => 'search'],
		];
	}
}
