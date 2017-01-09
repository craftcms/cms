<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\widgets;

use Craft;
use craft\base\Widget;
use craft\helpers\Json;

/**
 * Feed represents a Feed dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Feed extends Widget
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
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
     * @var int The maximum number of feed items to display
     */
    public $limit = 5;

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
        return Craft::$app->getView()->renderTemplate('_components/widgets/Feed/settings',
            [
                'widget' => $this
            ]);
    }

    /**
     * @inheritdoc
     */
    public function getIconPath()
    {
        return Craft::$app->getPath()->getResourcesPath().DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'widgets'.DIRECTORY_SEPARATOR.'feed.svg';
    }

    /**
     * @inheritdoc
     */
    public function getTitle(): string
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
            "new Craft.FeedWidget({$this->id}, ".
            Json::encode($this->url).', '.
            Json::encode($this->limit).');'
        );

        return Craft::$app->getView()->renderTemplate('_components/widgets/Feed/body',
            [
                'limit' => $this->limit
            ]);
    }
}
