<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Widgets;

use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Image\Enums\ImageTransformMode;
use CraftCms\Cms\Ui\Controls\Number;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use Override;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class MyDrafts extends Widget
{
    #[Override]
    public static function displayName(): string
    {
        return t('My Drafts');
    }

    #[Override]
    protected static function allowMultipleInstances(): bool
    {
        return false;
    }

    /**
     * @var int The total number of drafts that the widget should show
     */
    public int $limit = 10;

    #[Override]
    public static function icon(): string
    {
        return 'scribble';
    }

    #[Override]
    public function getRules(): array
    {
        return [
            'limit' => ['required', 'integer', 'min:1'],
        ];
    }

    #[Override]
    public function settingsUi(UiContext $context = new UiContext): Ui
    {
        return Ui::make([
            Field::make(t('Limit'))
                ->required()
                ->control(Number::make('limit')->value($this->limit)->min(1)->size(2)),
        ]);
    }

    public function component(): ?string
    {
        return 'craft:widget-my-drafts';
    }

    /** @return array{drafts: list<array{id: int|null, url: string|null, title: string, thumbHtml: string|null, draftName: string|null, section: string|null}>} */
    public function props(): array
    {
        return ['drafts' => $this->getDrafts()->map(fn (Entry $draft): array => [
            'id' => $draft->id,
            'url' => $draft->getCpEditUrl(),
            'title' => $draft->getUiLabel(),
            'thumbHtml' => $draft->getThumbHtml(40, ImageTransformMode::Crop),
            // Named drafts of published entries, as their chips label them, rather than new entries’ drafts.
            'draftName' => ! $draft->isProvisionalDraft && ! $draft->getIsUnpublishedDraft()
                ? ($draft->draftName ?: t('Draft'))
                : null,
            'section' => ($section = $draft->getSection()) ? t($section->name, category: 'site') : null,
        ])->values()->all()];
    }

    /** @return ElementCollection<int, Entry> */
    private function getDrafts(): ElementCollection
    {
        return Entry::find()
            ->drafts()
            ->status(null)
            ->draftCreator(currentUser()?->getCraftUserId())
            ->section('*')
            // Not drafts in sections the user can no longer view, which wouldn’t open.
            ->editable()
            ->site('*')
            ->unique()
            ->orderByDesc('dateUpdated')
            ->limit($this->limit)
            ->get();
    }
}
