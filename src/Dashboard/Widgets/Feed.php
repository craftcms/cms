<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Widgets;

use CraftCms\Cms\Form\Controls\Number;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\View\LegacyAssets\FeedAsset;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use Illuminate\Support\Facades\Cache;
use Override;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

class Feed extends Widget
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
    public function settingsForm(FormContext $context = new FormContext): Form
    {
        return Form::make([
            Field::make(t('URL'))
                ->required()
                ->control(Text::make('url')->value($this->url)),
            Field::make(t('Title'))
                ->required()
                ->control(Text::make('title')->value($this->title)),
            Field::make(t('Limit'))
                ->required()
                ->control(Number::make('limit')->value($this->limit)->min(1)),
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
        $userId = currentUser()?->getCraftUserId();

        if ($userId) {
            $key = sprintf('feed:%s:%s', $userId, $this->url);
            $data = Cache::get($key);
        } else {
            $data = null;
        }

        if ($data) {
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

        app(InternalAssetRegistry::class)->register(FeedAsset::class);
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
