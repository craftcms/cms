<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Widgets;

use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Number;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use Override;

use function CraftCms\Cms\t;

class RecentEntries extends Widget
{
    #[Override]
    public static function displayName(): string
    {
        return t('Recent Entries');
    }

    #[Override]
    public static function icon(): string
    {
        return 'clock';
    }

    /**
     * @var int|null The site ID that the widget should pull entries from
     */
    public ?int $siteId = null;

    /**
     * @var string|int[] The section IDs that the widget should pull entries from
     */
    public string|array $section = '*';

    /**
     * @var int The total number of entries that the widget should show
     */
    public int $limit = 10;

    /** @param array<string, mixed> $config */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this->siteId ??= Sites::getCurrentSite()->id;
    }

    #[Override]
    public function getRules(): array
    {
        return [
            'siteId' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer'],
        ];
    }

    #[Override]
    public function settingsForm(FormContext $context = new FormContext): Form
    {
        $form = Form::make();
        $editableSites = Sites::getEditableSites();

        if (Sites::isMultiSite() && $editableSites->count() > 1) {
            $form->add(Field::make(t('Site'))
                ->control(Choice::make('siteId')->value($this->siteId)->options($editableSites
                    ->map(fn ($site): array => [
                        'label' => t($site->getName(), category: 'site'),
                        'value' => $site->id,
                    ])
                    ->values()
                    ->all())));
        }

        return $form->add(
            Field::make(t('Section'))
                ->instructions(t('Which section do you want to pull recent entries from?'))
                ->control(Choice::make('section')->value($this->section)->options([
                    ['label' => t('All'), 'value' => '*'],
                    ...Sections::getAllSections()
                        ->filter(fn ($section): bool => $section->type !== SectionType::Single)
                        ->map(fn ($section): array => [
                            'label' => t($section->name, category: 'site'),
                            'value' => $section->id,
                        ])
                        ->values()
                        ->all(),
                ])),
            Field::make(t('Limit'))
                ->control(Number::make('limit')->value($this->limit)->min(1)),
        );
    }

    #[Override]
    public function getTitle(): string
    {
        if (is_numeric($this->section) && $section = Sections::getSectionById((int) $this->section)) {
            $title = t('Recent {section} Entries', [
                'section' => t($section->name, category: 'site'),
            ]);
        }

        /** @noinspection UnSafeIsSetOverArrayInspection - FP */
        $title ??= t('Recent Entries');

        // See if they are pulling entries from a different site
        $targetSiteId = $this->getTargetSiteId();

        if ($targetSiteId !== null && $targetSiteId !== Sites::getCurrentSite()->id) {
            $site = Sites::getSiteById($targetSiteId);

            if ($site) {
                $title = t('{title} ({site})', [
                    'title' => $title,
                    'site' => t($site->getName(), category: 'site'),
                ]);
            }
        }

        return $title;
    }

    public function component(): ?string
    {
        return 'craft:widget-recent-entries';
    }

    /** @return array{entries: list<array{url: ?string, title: string, dateCreated: ?string, dateLabel: string, author: ?string}>} */
    public function props(): array
    {
        return [
            'entries' => $this->getEntries()->map(fn (Entry $entry): array => [
                'url' => $entry->getCpEditUrl(),
                'title' => (string) $entry,
                'dateCreated' => $entry->dateCreated?->format(DATE_ATOM),
                'dateLabel' => I18N::getFormatter()->asTimestamp($entry->dateCreated, 'short'),
                'author' => Edition::get() !== Edition::Solo ? $entry->getAuthor()?->username : null,
            ])->values()->all(),
        ];
    }

    /**
     * Returns the recent entries, based on the widget settings and user permissions.
     */
    /** @return ElementCollection<int, Entry> */
    private function getEntries(): ElementCollection
    {
        $targetSiteId = $this->getTargetSiteId();

        if ($targetSiteId === null) {
            // Hopeless
            return new ElementCollection;
        }

        // Normalize the target section ID value.
        $editableSectionIds = $this->getEditableSectionIds();
        $targetSectionId = $this->section;

        if (! $targetSectionId || $targetSectionId === '*' || ! in_array($targetSectionId, $editableSectionIds)) {
            $targetSectionId = array_merge($editableSectionIds);
        }

        if (! $targetSectionId) {
            return new ElementCollection;
        }

        return Entry::find()
            ->sectionId($targetSectionId)
            ->editable()
            ->status(null)
            ->siteId($targetSiteId)
            ->limit($this->limit ?: 100)
            ->with(['author'])
            ->orderByDesc('dateCreated')
            ->get();
    }

    /**
     * Returns the Channel and Structure section IDs that the user is allowed to edit.
     */
    /** @return list<int> */
    private function getEditableSectionIds(): array
    {
        $sectionIds = [];

        foreach (Sections::getEditableSections() as $section) {
            if ($section->type !== SectionType::Single) {
                $sectionIds[] = $section->id;
            }
        }

        return $sectionIds;
    }

    /**
     * Returns the target site ID for the widget.
     */
    private function getTargetSiteId(): ?int
    {
        if (! Sites::isMultiSite()) {
            return $this->siteId;
        }

        // Make sure that the user is actually allowed to edit entries in the current site. Otherwise grab entries in
        // their first editable site.

        // Figure out which sites the user is actually allowed to edit
        $editableSiteIds = Sites::getEditableSiteIds();

        // If they aren't allowed to edit *any* sites, return false
        if ($editableSiteIds->isEmpty()) {
            return null;
        }

        // Figure out which site was selected in the settings
        $targetSiteId = $this->siteId;

        // Only use that site if it still exists and they're allowed to edit it.
        // Otherwise go with the first site that they are allowed to edit.
        if ($editableSiteIds->doesntContain($targetSiteId)) {
            return $editableSiteIds[0];
        }

        return $targetSiteId;
    }
}
