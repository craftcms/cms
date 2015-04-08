<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\widgets;

use Craft;
use craft\app\base\Widget;
use craft\app\helpers\JsonHelper;

/**
 * Feed represents a Feed dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Feed extends Widget
{
	// Static
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function displayName()
	{
		return Craft::t('app', 'Feed');
	}

	// Properties
	// =========================================================================

	/**
	 * @var string The feed URL
	 */
	public $url;

	/**
	 * @var string The feed title
	 */
	public $title;

	/**
	 * @var integer The maximum number of feed items to display
	 */
	public $limit;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		$rules = parent::rules();
		$rules[] = [['url', 'title'], 'required'];
		$rules[] = [['url'], 'url'];
		$rules[] = [['limit'], 'integer', 'min' => 1];
		return $rules;
	}

	/**
	 * @inheritdoc
	 */
	public function getSettingsHtml()
	{
		return Craft::$app->getView()->renderTemplate('_components/widgets/Feed/settings', [
			'widget' => $this
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @inheritdoc
	 */
	public function getBodyHtml()
	{
		Craft::$app->getView()->registerJsResource('js/FeedWidget.js');
		Craft::$app->getView()->registerJs(
			"new Craft.FeedWidget({$this->id}, " .
			JsonHelper::encode($this->url).', ' .
			JsonHelper::encode($this->limit).');'
		);

		return Craft::$app->getView()->renderTemplate('_components/widgets/Feed/body', [
			'limit' => $this->limit
		]);
	}
}
