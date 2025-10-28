<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Widgets;

use Craft;
use craft\web\assets\feed\FeedAsset;
use CraftCms\Cms\Support\Json;
use Illuminate\Support\Facades\Cache;

use function CraftCms\Cms\t;

final class Feed extends Widget
{
    public ?string $url = null;

    public ?string $title = null;

    public int $limit = 5;

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function displayName(): string
    {
        return t('Feed');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function icon(): string
    {
        return 'rss';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function getRules(): array
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
    #[\Override]
    public function getSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('_components/widgets/Feed/settings.twig',
            [
                'widget' => $this,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
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
            'url' => $this->url,
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
