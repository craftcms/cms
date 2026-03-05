<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Widgets;

use Craft;
use craft\web\assets\feed\FeedAsset;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Json;
use Illuminate\Support\Facades\Cache;
use Override;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

final class Feed extends Widget
{
    public ?string $url = null;

    public ?string $title = null;

    public int $limit = 5;

    #[Override]
    public static function displayName(): string
    {
        return t('Feed');
    }

    #[Override]
    public static function icon(): string
    {
        return 'rss';
    }

    #[Override]
    public function getRules(): array
    {
        return [
            'title' => ['required'],
            'url' => ['required', 'url'],
            'limit' => ['required', 'integer', 'min:1'],
        ];
    }

    #[Override]
    public function getSettingsHtml(): string
    {
        return template('_components/widgets/Feed/settings',
            [
                'widget' => $this,
            ]);
    }

    #[Override]
    public function getTitle(): ?string
    {
        return $this->title;
    }

    #[Override]
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

        Craft::$app->getView()->registerAssetBundle(FeedAsset::class);
        HtmlStack::js(
            "new Craft.FeedWidget($this->id, ".
            Json::encode($this->url).', '.
            Json::encode($this->limit).');'
        );

        return $this->render($data);
    }

    private function render(mixed $data): string
    {
        return template('_components/widgets/Feed/body', [
            'feed' => $data,
        ]);
    }
}
