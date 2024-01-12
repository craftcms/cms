<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\widgets;

use Craft;
use craft\base\Widget;
use craft\helpers\Json;
use craft\web\assets\feed\FeedAsset;

/**
 * Feed represents a Feed dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Feed extends Widget
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Feed');
    }

    /**
     * @inheritdoc
     */
    public static function icon(): ?string
    {
        return Craft::getAlias('@appicons/feed.svg');
    }

    /**
     * @var string|null The feed URL
     */
    public ?string $url = null;

    /**
     * @var string|null The feed title
     */
    public ?string $title = null;

    /**
     * @var int The maximum number of feed items to display
     */
    public int $limit = 5;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // Config normalization
        if (($config['limit'] ?? null) === '') {
            unset($config['limit']);
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['url', 'title'], 'required'];
        $rules[] = [['url'], 'url'];
        $rules[] = [['limit'], 'integer', 'min' => 1];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('_components/widgets/Feed/settings.twig',
            [
                'widget' => $this,
            ]);
    }

    /**
     * @inheritdoc
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @inheritdoc
     */
    public function getBodyHtml(): ?string
    {
        // See if it's already cached
        $data = Craft::$app->getCache()->get("feed:$this->url");

        if ($data) {
            $data['items'] = array_slice($data['items'] ?? [], 0, $this->limit);
        } else {
            // Fake it for now and fetch it later
            $data = [
                'direction' => 'ltr',
                'items' => [],
            ];

            for ($i = 0; $i < $this->limit; $i++) {
                $data['items'][] = [];
            }

            $view = Craft::$app->getView();
            $view->registerAssetBundle(FeedAsset::class);
            $view->registerJs(
                "new Craft.FeedWidget($this->id, " .
                Json::encode($this->url) . ', ' .
                Json::encode($this->limit) . ');'
            );
        }

        return Craft::$app->getView()->renderTemplate('_components/widgets/Feed/body.twig', [
            'feed' => $data,
        ]);
    }
}
