<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Widgets;

use CraftCms\Cms\Cp\Components\Field;
use CraftCms\Cms\Cp\Components\NumberInput;
use CraftCms\Cms\Cp\Components\TextInput;
use CraftCms\Cms\Cp\Forms\Form;
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
    public function getSettingsForm(bool $readOnly): Form
    {
        return Form::make([
            Field::make(TextInput::make()->name('url'))
                ->label(t('URL'))
                ->required()
                ->readOnly($readOnly),
            Field::make(TextInput::make()->name('title'))
                ->label(t('Title'))
                ->required()
                ->readOnly($readOnly),
            Field::make(NumberInput::make()->name('limit')->min(1))
                ->label(t('Limit'))
                ->required()
                ->readOnly($readOnly),
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
