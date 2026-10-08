<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Widgets;

use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Ui\Controls\Number;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use Illuminate\Support\Facades\Cache;
use Override;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
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
    public function settingsUi(UiContext $context = new UiContext): Ui
    {
        return Ui::make([
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

    public function component(): ?string
    {
        return 'craft:widget-feed';
    }

    /** @return array{url: ?string, limit: int, feed: mixed, formattingLocale: string} */
    public function props(): array
    {
        $userId = currentUser()?->getCraftUserId();

        return [
            'url' => $this->url,
            'limit' => $this->limit,
            'formattingLocale' => str_replace('_', '-', I18N::getFormattingLocale()->id),
            'feed' => $userId ? Cache::get(sprintf('feed:%s:%s', $userId, $this->url)) : null,
        ];
    }
}
