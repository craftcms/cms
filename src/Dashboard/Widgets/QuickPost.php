<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Widgets;

use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use Illuminate\Support\Facades\Auth;
use Override;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

class QuickPost extends Widget
{
    #[Override]
    public static function displayName(): string
    {
        return t('Quick Post');
    }

    #[Override]
    public static function icon(): string
    {
        return 'file-circle-plus';
    }

    #[Override]
    public static function isSelectable(): bool
    {
        return parent::isSelectable() && self::availableSections() !== [];
    }

    /**
     * @var int|null The site ID that the widget should create entries for.
     */
    public ?int $siteId = null;

    /**
     * @var int The ID of the section that the widget should create entries for.
     */
    public int $section;

    /**
     * @var int|null The ID of the entry type that the widget should create entries with.
     */
    public ?int $entryType = null;

    /**
     * @var string|null The custom widget title.
     */
    public ?string $customTitle = null;

    /**
     * @see Section()
     */
    private Section|false $_section;

    /**
     * @see EntryType()
     */
    private EntryType|false $_entryType;

    /** @param array<string, mixed> $config */
    public function __construct(array $config = [])
    {
        // If we're saving the widget settings, all of the section-specific
        // attributes will be tucked away in a 'sections' array
        if (isset($config['sections'], $config['section'])) {
            $sectionId = $config['section'];

            if (isset($config['sections'][$sectionId])) {
                $config = array_merge($config, $config['sections'][$sectionId]);
            }

            unset($config['sections']);
        }

        if (isset($config['customTitle']) && $config['customTitle'] === '') {
            unset($config['customTitle']);
        }

        unset($config['fields']);

        parent::__construct($config);
    }

    #[Override]
    public function getRules(): array
    {
        return [
            'section' => ['required', 'integer'],
            'entryType' => ['nullable', 'integer'],
        ];
    }

    #[Override]
    public function settingsForm(FormContext $context = new FormContext): ?Form
    {
        $sections = self::availableSections();

        if ($sections === []) {
            return null;
        }

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

        $section = $this->section() ?? $sections[0];
        $entryTypes = $section->getEntryTypes();

        return $form->add(
            Field::make(t('Section'))
                ->instructions(t('Which section do you want to save entries to?'))
                ->control(Choice::make('section')->value($section->id)->options(array_map(
                    fn (Section $section): array => [
                        'label' => t($section->name, category: 'site'),
                        'value' => $section->id,
                    ],
                    $sections,
                ))),
            Field::make(t('Entry Type'))
                ->instructions(count($entryTypes) > 1 ? t('Which type of entries do you want to create?') : null)
                ->control(Choice::make('entryType')->value($this->entryType()?->id)->options(array_map(
                    fn (EntryType $entryType): array => [
                        'label' => t($entryType->name, category: 'site'),
                        'value' => $entryType->id,
                    ],
                    $entryTypes,
                ))),
            Field::make(t('Widget Title'))
                ->control(Text::make('customTitle')
                    ->value($this->customTitle)
                    ->placeholder(t('Create a new {section} entry', ['section' => $section->getUiLabel()]))),
        );
    }

    #[Override]
    public function getTitle(): string
    {
        if (isset($this->customTitle)) {
            return t($this->customTitle, category: 'site');
        }

        $entryType = $this->entryType();
        if (! $entryType) {
            return self::displayName();
        }

        return t('Create a new {section} entry', [
            'section' => t($this->section()?->name, category: 'site'),
        ]);
    }

    #[Override]
    public function getBodyHtml(): string
    {
        $section = $this->section();
        if (! $section) {
            return Html::tag('p', t('No section has been selected yet.'));
        }

        $entryType = $this->entryType();
        if (! $entryType) {
            return Html::tag('p', t('No entry types exist for this section.'));
        }

        $siteId = $this->siteId();
        if (! $siteId) {
            return Html::tag('p', t('You’re not permitted to edit any of this section’s sites.'));
        }

        $buttonId = sprintf('quickpost%s', mt_rand());

        HtmlStack::jsWithVars(fn ($buttonId, $params, $elementType) => <<<JS
(() => {
  const button = $('#' + $buttonId);
  button.on('activate', async () => {
    button.addClass('loading');
    let entry;
    try {
      const response = await Craft.sendActionRequest('POST', 'entries/create', {
        data: $params,
      })
      entry = response.data.entry;
    } finally {
      button.removeClass('loading');
    }
    const slideout = Craft.createElementEditor($elementType, {
      siteId: entry.siteId,
      elementId: entry.id,
      draftId: entry.draftId,
      params: {
        fresh: 1,
      },
    })

    slideout.on('submit', ({data}) => {
      // Are there any Recent Entries widgets to notify?
      if (typeof Craft.RecentEntriesWidget !== 'undefined') {
        for (const widget of Craft.RecentEntriesWidget.instances) {
          if (
            !widget.params.sectionId ||
            widget.params.sectionId == entry.sectionId
          ) {
            widget.addEntry({
              url: data.cpEditUrl,
              title: data.title,
              dateCreated: data.dateCreated,
            });
          }
        }
      }
    });
  });
})();
JS, [
            $buttonId,
            [
                'siteId' => $this->siteId(),
                'section' => $section->handle,
                'type' => $entryType->handle,
                'authorId' => Auth::id(),
            ],
            Entry::class,
        ]);

        return template('_includes/forms/button', [
            'id' => $buttonId,
            'class' => ['huge', 'icon', 'add', 'dashed', 'fullwidth'],
            'label' => mb_ucfirst(t('Create {type}', [
                'type' => Entry::lowerDisplayName(),
            ])),
            'spinner' => true,
        ]);
    }

    private function siteId(): ?int
    {
        $editableSiteIds = Sites::getEditableSiteIds();

        if ($this->siteId && $editableSiteIds->contains($this->siteId)) {
            return $this->siteId;
        }

        return $editableSiteIds->intersect($this->section()->getSiteIds())->first();
    }

    private function section(): ?Section
    {
        if (! isset($this->_section)) {
            if (isset($this->section)) {
                $section = Sections::getEditableSections()->first(
                    fn (Section $section) => $section->id === $this->section,
                );
            } else {
                $section = null;
            }
            $this->_section = $section ?? false;
        }

        return $this->_section ?: null;
    }

    private function entryType(): ?EntryType
    {
        if (! isset($this->_entryType)) {
            $section = $this->section();
            if ($section && isset($this->entryType)) {
                $entryType = Arr::first(
                    $section->getEntryTypes(),
                    fn (EntryType $entryType) => $entryType->id === $this->entryType,
                );
            } else {
                $entryType = null;
            }
            $this->_entryType = $entryType ?? $section?->getEntryTypes()[0] ?? false;
        }

        return $this->_entryType ?: null;
    }

    /** @return list<Section> */
    private static function availableSections(): array
    {
        return Sections::getAllSections()
            ->filter(fn (Section $section): bool => $section->type !== SectionType::Single
                && (currentUser()?->can('createEntries:'.$section->uid) ?? false))
            ->values()
            ->all();
    }
}
