<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\widgets;

use Craft;
use craft\base\Widget;
use craft\elements\Entry;
use craft\helpers\ArrayHelper;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use craft\models\EntryType;
use craft\models\Section;

/**
 * QuickPost represents a Quick Post dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class QuickPost extends Widget
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Quick Post');
    }

    /**
     * @inheritdoc
     */
    public static function icon(): ?string
    {
        return 'file-circle-plus';
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
     * @since 5.6.0
     */
    public ?string $customTitle = null;

    /**
     * @var Section|false
     * @see section()
     */
    private Section|false $_section;

    /**
     * @var EntryType|false
     * @see entryType()
     */
    private EntryType|false $_entryType;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
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

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['section'], 'required'];
        $rules[] = [['section', 'entryType'], 'integer'];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        // Find the sections the user has permission to create entries in
        $sections = [];

        foreach (Craft::$app->getEntries()->getAllSections() as $section) {
            if ($section->type !== Section::TYPE_SINGLE) {
                if (Craft::$app->getUser()->checkPermission('createEntries:' . $section->uid)) {
                    $sections[] = $section;
                }
            }
        }

        return Craft::$app->getView()->renderTemplate('_components/widgets/QuickPost/settings.twig', [
            'sections' => $sections,
            'widget' => $this,
            'siteId' => $this->siteId,
            'section' => $this->section(),
            'entryType' => $this->entryType(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getTitle(): ?string
    {
        if (isset($this->customTitle)) {
            return Craft::t('site', $this->customTitle);
        }

        $entryType = $this->entryType();
        if (!$entryType) {
            return static::displayName();
        }

        return Craft::t('app', 'Create a new {section} entry', [
            'section' => Craft::t('site', $this->section()->name),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getBodyHtml(): ?string
    {
        $section = $this->section();
        if (!$section) {
            return Html::tag('p', Craft::t('app', 'No section has been selected yet.'));
        }

        $entryType = $this->entryType();
        if (!$entryType) {
            return Html::tag('p', Craft::t('app', 'No entry types exist for this section.'));
        }

        $siteId = $this->siteId();
        if (!$siteId) {
            return Html::tag('p', Craft::t('app', 'You’re not permitted to edit any of this section’s sites.'));
        }

        $buttonId = sprintf('quickpost%s', mt_rand());

        $view = Craft::$app->getView();
        $view->registerJsWithVars(fn($buttonId, $params, $elementType) => <<<JS
(() => {
  const button = $('#' + $buttonId);
  button.on('activate', async () => {
    button.addClass('loading');
    let entry;
    try {
      const response = await Craft.sendActionRequest('POST', 'entries/create', {
        data: $params,
      });
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
    });
    
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
                'authorId' => Craft::$app->getUser()->getId(),
            ],
            Entry::class,
        ]);

        return $view->renderTemplate('_includes/forms/button.twig', [
            'id' => $buttonId,
            'class' => ['huge', 'icon', 'add', 'dashed', 'fullwidth'],
            'label' => StringHelper::upperCaseFirst(Craft::t('app', 'Create {type}', [
                'type' => Entry::lowerDisplayName(),
            ])),
            'spinner' => true,
        ]);
    }

    private function siteId(): ?int
    {
        $editableSiteIds = Craft::$app->getSites()->getEditableSiteIds();

        if ($this->siteId && in_array($this->siteId, $editableSiteIds)) {
            return $this->siteId;
        }

        $possibleSiteIds = array_intersect($editableSiteIds, $this->section()->getSiteIds());
        return ArrayHelper::firstValue($possibleSiteIds);
    }

    private function section(): ?Section
    {
        if (!isset($this->_section)) {
            if (isset($this->section)) {
                $section = ArrayHelper::firstWhere(
                    Craft::$app->getEntries()->getEditableSections(),
                    fn(Section $section) => $section->id === $this->section,
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
        if (!isset($this->_entryType)) {
            $section = $this->section();
            if ($section && isset($this->entryType)) {
                $entryType = ArrayHelper::firstWhere(
                    $section->getEntryTypes(),
                    fn(EntryType $entryType) => $entryType->id === $this->entryType,
                );
            } else {
                $entryType = null;
            }
            $this->_entryType = $entryType ?? $section?->getEntryTypes()[0] ?? false;
        }

        return $this->_entryType ?: null;
    }
}
