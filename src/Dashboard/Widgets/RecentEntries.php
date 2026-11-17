<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Widgets;

use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Condition\Enums\GroupOperator;
use CraftCms\Cms\Cp\RequestedSite;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Conditions\SiteConditionRule;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Entry\Conditions\EntryCondition;
use CraftCms\Cms\Entry\Conditions\SectionConditionRule;
use CraftCms\Cms\Entry\Conditions\SectionTypeConditionRule;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Image\Enums\ImageTransformMode;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Conditions;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Translation\Locale;
use CraftCms\Cms\Ui\Controls\ConditionBuilder;
use CraftCms\Cms\Ui\Controls\Number;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\Group;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\User\Elements\User;
use Override;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
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
     * @var int The total number of entries that the widget should show
     */
    public int $limit = 10;

    /** The width and height of entries’ thumbnails, in pixels. */
    private const int THUMB_SIZE = 40;

    /**
     * @var string|null The widget’s title, if it shouldn’t be based on the selected section
     */
    public ?string $customTitle = null;

    /**
     * @see getCondition()
     * @see setCondition()
     *
     * @var array<string, mixed>|EntryCondition|null
     */
    private array|null|EntryCondition $_condition = null;

    /** @param array<string, mixed> $config */
    public function __construct(array $config = [])
    {
        // Map the legacy `siteId` and `section` settings to condition rules
        $siteId = Arr::pull($config, 'siteId');
        $section = Arr::pull($config, 'section');

        if (isset($config['customTitle']) && $config['customTitle'] === '') {
            unset($config['customTitle']);
        }

        $config['condition'] ??= [
            'class' => EntryCondition::class,
            'elementType' => Entry::class,
            'conditionRules' => self::legacyConditionRules($siteId, $section),
        ];

        parent::__construct($config);
    }

    /**
     * Returns condition rule configs that match the legacy `siteId` and `section` settings.
     *
     * @return list<array<string, mixed>>
     */
    private static function legacyConditionRules(mixed $siteId, mixed $section): array
    {
        $rules = [];

        if (is_numeric($siteId) && Sites::isMultiSite() && $site = Sites::getSiteById((int) $siteId)) {
            $rules[] = [
                'class' => SiteConditionRule::class,
                'operator' => 'in',
                'values' => [$site->uid],
            ];
        }

        $sectionUids = collect(is_array($section) ? $section : [$section])
            ->filter(fn (mixed $sectionId): bool => is_numeric($sectionId))
            ->map(fn (mixed $sectionId): ?string => Sections::getSectionById((int) $sectionId)?->uid)
            ->filter()
            ->values()
            ->all();

        if ($sectionUids !== []) {
            $rules[] = [
                'class' => SectionConditionRule::class,
                'operator' => 'in',
                'values' => $sectionUids,
            ];
        } else {
            // “All” sections meant all channels and structures
            $rules[] = [
                'class' => SectionTypeConditionRule::class,
                'operator' => 'in',
                'values' => [SectionType::Channel->value, SectionType::Structure->value],
            ];
        }

        return $rules;
    }

    /**
     * Returns the condition that entries must match to be shown by the widget.
     */
    public function getCondition(): EntryCondition
    {
        if (! $this->_condition instanceof EntryCondition) {
            $config = is_array($this->_condition) ? $this->_condition : [];
            /** @var EntryCondition $condition */
            $condition = Conditions::createCondition([
                ...$config,
                'class' => EntryCondition::class,
                'elementType' => Entry::class,
            ]);
            $this->_condition = $condition;
        }

        return $this->_condition;
    }

    /**
     * Sets the condition that entries must match to be shown by the widget.
     *
     * @param  EntryCondition|array<string, mixed>|null  $condition
     */
    public function setCondition(EntryCondition|array|null $condition): void
    {
        $this->_condition = $condition;
    }

    #[Override]
    public function settingsAttributes(): array
    {
        return [
            ...parent::settingsAttributes(),
            'condition',
        ];
    }

    #[Override]
    public function getSettings(): array
    {
        return [
            ...parent::getSettings(),
            'condition' => $this->getCondition()->getConfig(),
        ];
    }

    #[Override]
    public function getRules(): array
    {
        return [
            'limit' => ['nullable', 'integer'],
            'customTitle' => ['nullable', 'string', 'max:255'],
        ];
    }

    #[Override]
    public function settingsUi(UiContext $context = new UiContext): Ui
    {
        return Ui::make()->add(
            Field::make(t('{type} Criteria', ['type' => Entry::displayName()]))
                ->control(ConditionBuilder::make('condition')
                    ->conditionClass(EntryCondition::class)
                    ->addRuleLabel(t('Add a filter'))
                    ->value($this->getCondition()->getConfig())
                    ->reactive()),
            Field::make(t('Limit'))
                ->control(Number::make('limit')->value($this->limit)->min(1)),
            Group::make('recent-entries-title', [
                Field::make(t('Widget Title'))
                    ->control(Text::make('customTitle')
                        ->value($this->customTitle)
                        ->placeholder($this->defaultTitle())),
            ])->dependsOn('condition'),
        );
    }

    #[Override]
    public function getTitle(): string
    {
        if (isset($this->customTitle)) {
            return t($this->customTitle, category: 'site');
        }

        return $this->defaultTitle();
    }

    /**
     * Returns the title the widget gets without a custom one, based on the selected section.
     */
    private function defaultTitle(): string
    {
        if ($section = $this->singleSection()) {
            return t('Recent {section} Entries', [
                'section' => t($section->name, category: 'site'),
            ]);
        }

        return t('Recent Entries');
    }

    /**
     * Returns the section the widget pulls all of its entries from, if its criteria limit it to a single one.
     */
    private function singleSection(): ?Section
    {
        $sectionUid = $this->singleRuleValue(SectionConditionRule::class);

        return $sectionUid !== null ? Sections::getSectionByUid($sectionUid) : null;
    }

    /**
     * Returns the value of a top-level multi-select rule, if the condition limits entries to a single value with it.
     *
     * @param  class-string<BaseMultiSelectConditionRule>  $ruleClass
     */
    private function singleRuleValue(string $ruleClass): ?string
    {
        $group = $this->getCondition()->getConditionRules();

        if ($group->operator !== GroupOperator::And) {
            return null;
        }

        $rules = array_values(array_filter($group->getRules(), fn ($rule): bool => $rule instanceof $ruleClass));

        if (count($rules) !== 1) {
            return null;
        }

        $config = $rules[0]->getConfig();

        if (($config['operator'] ?? null) !== 'in' || count($config['values'] ?? []) !== 1) {
            return null;
        }

        return (string) $config['values'][0];
    }

    public function component(): ?string
    {
        return 'craft:widget-recent-entries';
    }

    /** @return array{entries: list<array{id: int|null, url: ?string, title: string, thumbHtml: ?string, section: ?string, dateCreated: ?string, dateLabel: ?string, authors: list<array{id: int|null, name: string, thumbHtml: ?string}>}>} */
    public function props(): array
    {
        // Entries pulled from a single section don't need it named, as the default title already does.
        $showSections = $this->singleSection() === null;

        return [
            'entries' => $this->getEntries()->map(fn (Entry $entry): array => [
                'id' => $entry->id,
                'url' => $entry->getCpEditUrl(),
                'title' => $entry->getUiLabel(),
                'thumbHtml' => $entry->getThumbHtml(self::THUMB_SIZE, ImageTransformMode::Crop),
                'section' => $showSections && ($section = $entry->getSection())
                    ? t($section->name, category: 'site')
                    : null,
                'dateCreated' => $entry->dateCreated?->format(DATE_ATOM),
                'dateLabel' => $entry->dateCreated ? I18N::getFormatter()->asTimestamp($entry->dateCreated, Locale::LENGTH_MEDIUM) : null,
                'authors' => Edition::get() !== Edition::Solo
                    ? array_values(array_map(fn (User $author): array => [
                        'id' => $author->id,
                        'name' => $author->getUiLabel(),
                        'thumbHtml' => self::authorThumbHtml($author),
                    ], $entry->getAuthors()))
                    : [],
            ])->values()->all(),
        ];
    }

    /**
     * Returns an author's thumbnail without its SVG title, which browsers show as a tooltip of their own.
     *
     * The widget names each author with a tooltip instead.
     */
    private static function authorThumbHtml(User $author): ?string
    {
        $html = $author->getThumbHtml(24);

        return $html === null ? null : preg_replace('#<title>.*?</title>#s', '', $html);
    }

    /**
     * Returns the recent entries, based on the widget settings and user permissions.
     */
    /** @return ElementCollection<int, Entry> */
    private function getEntries(): ElementCollection
    {
        $editableSiteIds = Sites::getEditableSiteIds()->all();
        $editableSectionIds = Sections::getEditableSections()->map(fn ($section) => $section->id)->values()->all();

        if ($editableSiteIds === [] || $editableSectionIds === []) {
            return new ElementCollection;
        }

        $query = Entry::find()
            ->sectionId($editableSectionIds)
            ->editable()
            ->status(null)
            ->siteId($editableSiteIds)
            ->unique()
            ->preferSites(array_filter([app(RequestedSite::class)->get()?->id]))
            ->limit($this->limit ?: 100)
            ->with(['authors'])
            ->orderByDesc('dateCreated');

        $this->getCondition()->modifyQuery($query);

        return $query->get();
    }
}
