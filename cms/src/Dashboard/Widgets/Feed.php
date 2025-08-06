<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace CraftCms\Cms\Dashboard\Widgets;

use Craft;
use craft\helpers\Json;
use craft\web\assets\feed\FeedAsset;
use Illuminate\Support\Facades\Cache;

/**
 * Feed represents a Feed dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 6.0.0
 */
class Feed extends Widget
{
    /**
     * {@inheritdoc}
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Feed');
    }

    /**
     * {@inheritdoc}
     */
    public static function icon(): ?string
    {
        return 'rss';
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
     * {@inheritdoc}
     */
    /**
     * {@inheritdoc}
     */
    public static function getSettingsRules(): array
    {
        return [
            'title' => ['required'],
            'url' => ['required', 'url'],
            'limit' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('_components/widgets/Feed/settings.twig',
            [
                'widget' => $this,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * {@inheritdoc}
     */
    public function getBodyHtml(): string
    {
        // See if it's already cached
        if ($data = Cache::get("feed:$this->url")) {
            $data['items'] = array_slice($data['items'] ?? [], 0, $this->limit);

            return $this->render($data);
        }

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
            "new Craft.FeedWidget($this->id, ".
            Json::encode($this->url).', '.
            Json::encode($this->limit).');'
        );

        return $this->render($data);
    }

    protected function render(mixed $data): string
    {
        return Craft::$app->getView()->renderTemplate('_components/widgets/Feed/body.twig', [
            'feed' => $data,
        ]);
    }
}
