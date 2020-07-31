<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements;

use Craft;
use craft\base\Element;
use craft\base\Field;
use craft\behaviors\RevisionBehavior;
use craft\controllers\ElementIndexesController;
use craft\db\Query;
use craft\db\Table;
use craft\elements\actions\Delete;
use craft\elements\actions\Duplicate;
use craft\elements\actions\Edit;
use craft\elements\actions\NewChild;
use craft\elements\actions\Restore;
use craft\elements\actions\SetStatus;
use craft\elements\actions\View;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\EntryQuery;
use craft\errors\UnsupportedSiteException;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\models\EntryType;
use craft\models\Section;
use craft\models\Site;
use craft\records\Entry as EntryRecord;
use craft\services\Structures;
use craft\validators\DateTimeValidator;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Entry represents an entry element.
 *
 * @property User|null $author the entry's author
 * @property Section $section the entry's section
 * @property EntryType $type the entry type
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Entry extends Element
{
    const STATUS_LIVE = 'live';
    const STATUS_PENDING = 'pending';
    const STATUS_EXPIRED = 'expired';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Entry');
    }

    /**
     * @inheritdoc
     */
    public static function lowerDisplayName(): string
    {
        return Craft::t('app', 'entry');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('app', 'Entries');
    }

    /**
     * @inheritdoc
     */
    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('app', 'entries');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle()
    {
        return 'entry';
    }

    /**
     * @inheritdoc
     */
    public static function trackChanges(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasUris(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_LIVE => Craft::t('app', 'Live'),
            self::STATUS_PENDING => Craft::t('app', 'Pending'),
            self::STATUS_EXPIRED => Craft::t('app', 'Expired'),
            self::STATUS_DISABLED => Craft::t('app', 'Disabled')
        ];
    }

    /**
     * @inheritdoc
     * @return EntryQuery The newly created [[EntryQuery]] instance.
     */
    public static function find(): ElementQueryInterface
    {
        return new EntryQuery(static::class);
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context = null): array
    {
        if ($context === 'index') {
            $sections = Craft::$app->getSections()->getEditableSections();
            $editable = true;
        } else {
            $sections = Craft::$app->getSections()->getAllSections();
            $editable = false;
        }

        $sectionIds = [];
        $singleSectionIds = [];
        $sectionsByType = [];

        foreach ($sections as $section) {
            $sectionIds[] = $section->id;

            if ($section->type == Section::TYPE_SINGLE) {
                $singleSectionIds[] = $section->id;
            } else {
                $sectionsByType[$section->type][] = $section;
            }
        }

        $sources = [
            [
                'key' => '*',
                'label' => Craft::t('app', 'All entries'),
                'criteria' => [
                    'sectionId' => $sectionIds,
                    'editable' => $editable
                ],
                'defaultSort' => ['postDate', 'desc']
            ]
        ];

        if (!empty($singleSectionIds)) {
            $sources[] = [
                'key' => 'singles',
                'label' => Craft::t('app', 'Singles'),
                'criteria' => [
                    'sectionId' => $singleSectionIds,
                    'editable' => $editable
                ],
                'defaultSort' => ['title', 'asc']
            ];
        }

        $sectionTypes = [
            Section::TYPE_CHANNEL => Craft::t('app', 'Channels'),
            Section::TYPE_STRUCTURE => Craft::t('app', 'Structures')
        ];

        foreach ($sectionTypes as $type => $heading) {
            if (!empty($sectionsByType[$type])) {
                $sources[] = ['heading' => $heading];

                foreach ($sectionsByType[$type] as $section) {
                    /** @var Section $section */
                    $source = [
                        'key' => 'section:' . $section->uid,
                        'label' => Craft::t('site', $section->name),
                        'sites' => $section->getSiteIds(),
                        'data' => [
                            'type' => $type,
                            'handle' => $section->handle
                        ],
                        'criteria' => [
                            'sectionId' => $section->id,
                            'editable' => $editable
                        ]
                    ];

                    if ($type == Section::TYPE_STRUCTURE) {
                        $source['defaultSort'] = ['structure', 'asc'];
                        $source['structureId'] = $section->structureId;
                        $source['structureEditable'] = Craft::$app->getUser()->checkPermission('publishEntries:' . $section->uid);
                    } else {
                        $source['defaultSort'] = ['postDate', 'desc'];
                    }

                    $sources[] = $source;
                }
            }
        }

        return $sources;
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    protected static function defineFieldLayouts(string $source): array
    {
        // Get all the sections covered by this source
        $sections = [];
        if ($source === '*') {
            $sections = Craft::$app->getSections()->getAllSections();
        } else if ($source === 'singles') {
            $sections = Craft::$app->getSections()->getSectionsByType(Section::TYPE_SINGLE);
        } else if (
            preg_match('/^section:(.+)$/', $source, $matches) &&
            $section = Craft::$app->getSections()->getSectionByUid($matches[1])
        ) {
            $sections = [$section];
        }

        $fieldLayouts = [];
        foreach ($sections as $section) {
            foreach ($section->getEntryTypes() as $entryType) {
                $fieldLayouts[] = $entryType->getFieldLayout();
            }
        }
        return $fieldLayouts;
    }

    /**
     * @inheritdoc
     */
    protected static function defineActions(string $source = null): array
    {
        // Get the selected site
        $controller = Craft::$app->controller;
        if ($controller instanceof ElementIndexesController) {
            /** @var ElementQuery $elementQuery */
            $elementQuery = $controller->getElementQuery();
        } else {
            $elementQuery = null;
        }
        $site = $elementQuery && $elementQuery->siteId
            ? Craft::$app->getSites()->getSiteById($elementQuery->siteId)
            : Craft::$app->getSites()->getCurrentSite();

        // Get the section(s) we need to check permissions on
        switch ($source) {
            case '*':
                $sections = Craft::$app->getSections()->getEditableSections();
                break;
            case 'singles':
                $sections = Craft::$app->getSections()->getSectionsByType(Section::TYPE_SINGLE);
                break;
            default:
                if (preg_match('/^section:(\d+)$/', $source, $matches)) {
                    if (($section = Craft::$app->getSections()->getSectionById($matches[1])) !== null) {
                        $sections = [$section];
                    }
                } else if (preg_match('/^section:(.+)$/', $source, $matches)) {
                    if (($section = Craft::$app->getSections()->getSectionByUid($matches[1])) !== null) {
                        $sections = [$section];
                    }
                }
        }

        // Now figure out what we can do with these
        $actions = [];
        $elementsService = Craft::$app->getElements();

        /** @var Section[] $sections */
        if (!empty($sections)) {
            $userSession = Craft::$app->getUser();
            $canSetStatus = true;
            $canEdit = false;

            foreach ($sections as $section) {
                $canPublishEntries = $userSession->checkPermission('publishEntries:' . $section->uid);

                // Only show the Set Status action if we're sure they can make changes in all the sections
                if (!(
                    $canPublishEntries &&
                    ($section->type == Section::TYPE_SINGLE || $userSession->checkPermission('publishPeerEntries:' . $section->uid))
                )
                ) {
                    $canSetStatus = false;
                }

                // Show the Edit action if they can publish changes to *any* of the sections
                // (the trigger will disable itself for entries that aren't editable)
                if ($canPublishEntries) {
                    $canEdit = true;
                }
            }

            // Set Status
            if ($canSetStatus) {
                $actions[] = SetStatus::class;
            }

            // Edit
            if ($canEdit) {
                $actions[] = $elementsService->createAction([
                    'type' => Edit::class,
                    'label' => Craft::t('app', 'Edit entry'),
                ]);
            }

            // View
            $showViewAction = ($source === '*' || $source === 'singles');

            if (!$showViewAction) {
                // They are viewing a specific section. See if it has URLs for the requested site
                if (isset($sections[0]->siteSettings[$site->id]) && $sections[0]->siteSettings[$site->id]->hasUrls) {
                    $showViewAction = true;
                }
            }

            if ($showViewAction) {
                // View
                $actions[] = $elementsService->createAction([
                    'type' => View::class,
                    'label' => Craft::t('app', 'View entry'),
                ]);
            }

            // Channel/Structure-only actions
            if ($source !== '*' && $source !== 'singles') {
                $section = $sections[0];

                // New child?
                if (
                    $section->type == Section::TYPE_STRUCTURE &&
                    $userSession->checkPermission('createEntries:' . $section->uid)
                ) {
                    $structure = Craft::$app->getStructures()->getStructureById($section->structureId);

                    if ($structure) {
                        $newChildUrl = 'entries/' . $section->handle . '/new';

                        if (Craft::$app->getIsMultiSite()) {
                            $newChildUrl .= '?site=' . $site->handle;
                        }

                        $actions[] = $elementsService->createAction([
                            'type' => NewChild::class,
                            'label' => Craft::t('app', 'Create a new child entry'),
                            'maxLevels' => $structure->maxLevels,
                            'newChildUrl' => $newChildUrl,
                        ]);
                    }
                }

                // Duplicate
                if (
                    $userSession->checkPermission('createEntries:' . $section->uid) &&
                    $userSession->checkPermission('publishEntries:' . $section->uid)
                ) {
                    $actions[] = Duplicate::class;

                    if ($section->type === Section::TYPE_STRUCTURE && $section->maxLevels != 1) {
                        $actions[] = [
                            'type' => Duplicate::class,
                            'deep' => true,
                        ];
                    }
                }

                // Delete?
                if (
                    $userSession->checkPermission('deleteEntries:' . $section->uid) &&
                    $userSession->checkPermission('deletePeerEntries:' . $section->uid)
                ) {
                    $actions[] = Delete::class;

                    if ($section->type === Section::TYPE_STRUCTURE) {
                        $actions[] = [
                            'type' => Delete::class,
                            'withDescendants' => true,
                        ];
                    }
                }
            }
        }

        // Restore
        $actions[] = $elementsService->createAction([
            'type' => Restore::class,
            'successMessage' => Craft::t('app', 'Entries restored.'),
            'partialSuccessMessage' => Craft::t('app', 'Some entries restored.'),
            'failMessage' => Craft::t('app', 'Entries not restored.'),
        ]);

        return $actions;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('app', 'Title'),
            'slug' => Craft::t('app', 'Slug'),
            'uri' => Craft::t('app', 'URI'),
            'postDate' => Craft::t('app', 'Post Date'),
            'expiryDate' => Craft::t('app', 'Expiry Date'),
            [
                'label' => Craft::t('app', 'Date Created'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated'
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated'
            ],
            [
                'label' => Craft::t('app', 'ID'),
                'orderBy' => 'elements.id',
                'attribute' => 'id',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        $attributes = [
            'title' => ['label' => Craft::t('app', 'Title')],
            'section' => ['label' => Craft::t('app', 'Section')],
            'type' => ['label' => Craft::t('app', 'Entry Type')],
            'author' => ['label' => Craft::t('app', 'Author')],
            'slug' => ['label' => Craft::t('app', 'Slug')],
            'uri' => ['label' => Craft::t('app', 'URI')],
            'postDate' => ['label' => Craft::t('app', 'Post Date')],
            'expiryDate' => ['label' => Craft::t('app', 'Expiry Date')],
            'link' => ['label' => Craft::t('app', 'Link'), 'icon' => 'world'],
            'id' => ['label' => Craft::t('app', 'ID')],
            'uid' => ['label' => Craft::t('app', 'UID')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
            'revisionNotes' => ['label' => Craft::t('app', 'Revision Notes')],
            'revisionCreator' => ['label' => Craft::t('app', 'Last Edited By')],
        ];

        // Hide Author & Last Edited By from Craft Solo
        if (Craft::$app->getEdition() !== Craft::Pro) {
            unset($attributes['author'], $attributes['revisionCreator']);
        }

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = [];

        if ($source === '*') {
            $attributes[] = 'section';
        }

        if ($source !== 'singles') {
            $attributes[] = 'postDate';
            $attributes[] = 'expiryDate';
        }

        $attributes[] = 'author';
        $attributes[] = 'link';

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle)
    {
        if ($handle === 'author') {
            // Get the source element IDs
            $sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');

            $map = (new Query())
                ->select(['id as source', 'authorId as target'])
                ->from([Table::ENTRIES])
                ->where(['and', ['id' => $sourceElementIds], ['not', ['authorId' => null]]])
                ->all();

            return [
                'elementType' => User::class,
                'map' => $map
            ];
        }

        return parent::eagerLoadingMap($sourceElements, $handle);
    }

    /**
     * @inheritdoc
     */
    public static function gqlTypeNameByContext($context): string
    {
        /** @var EntryType $context */
        return self::_getGqlIdentifierByContext($context) . '_Entry';
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public static function gqlMutationNameByContext($context): string
    {
        /** @var EntryType $context */
        return 'save_' . self::_getGqlIdentifierByContext($context) . '_Entry';
    }

    /**
     * @inheritdoc
     */
    public static function gqlScopesByContext($context): array
    {
        /** @var EntryType $context */
        return [
            'sections.' . $context->getSection()->uid,
            'entrytypes.' . $context->uid,
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function prepElementQueryForTableAttribute(ElementQueryInterface $elementQuery, string $attribute)
    {
        switch ($attribute) {
            case 'author':
                $elementQuery->andWith('author');
                break;
            case 'revisionNotes':
                $elementQuery->andWith('currentRevision');
                break;
            case 'revisionCreator':
                $elementQuery->andWith('currentRevision.revisionCreator');
                break;
            default:
                parent::prepElementQueryForTableAttribute($elementQuery, $attribute);
        }
    }

    /**
     * @var int|null Section ID
     * ---
     * ```php
     * echo $entry->sectionId;
     * ```
     * ```twig
     * {{ entry.sectionId }}
     * ```
     */
    public $sectionId;

    /**
     * @var int|null Type ID
     * ---
     * ```php
     * echo $entry->typeId;
     * ```
     * ```twig
     * {{ entry.typeId }}
     * ```
     */
    public $typeId;

    /**
     * @var int|null Author ID
     * ---
     * ```php
     * echo $entry->authorId;
     * ```
     * ```twig
     * {{ entry.authorId }}
     * ```
     */
    public $authorId;

    /**
     * @var \DateTime|null Post date
     * ---
     * ```php
     * echo Craft::$app->formatter->asDate($entry->postDate, 'short');
     * ```
     * ```twig
     * {{ entry.postDate|date('short') }}
     * ```
     */
    public $postDate;

    /**
     * @var \DateTime|null Expiry date
     * ---
     * ```php
     * if ($entry->expiryDate) {
     *     echo Craft::$app->formatter->asDate($entry->expiryDate, 'short');
     * }
     * ```
     * ```twig
     * {% if entry.expiryDate %}
     *     {{ entry.expiryDate|date('short') }}
     * {% endif %}
     * ```
     */
    public $expiryDate;

    /**
     * @var int|null New parent ID
     * @internal
     */
    public $newParentId;

    /**
     * @var bool Whether the entry was deleted along with its entry type
     * @see beforeDelete()
     * @internal
     */
    public $deletedWithEntryType = false;

    /**
     * @var User|null|false
     */
    private $_author;

    /**
     * @var int|null
     */
    private $_oldTypeId;

    /**
     * @var bool|null
     * @see _hasNewParent()
     */
    private $_hasNewParent;

    /**
     * @inheritdoc
     */
    public function __clone()
    {
        parent::__clone();
        $this->_hasNewParent = null;
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function init()
    {
        parent::init();
        $this->_oldTypeId = $this->typeId;
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        $names = parent::extraFields();
        $names[] = 'author';
        $names[] = 'section';
        $names[] = 'type';
        return $names;
    }

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'postDate';
        $attributes[] = 'expiryDate';
        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['sectionId', 'typeId', 'authorId', 'newParentId'], 'number', 'integerOnly' => true];
        $rules[] = [['postDate', 'expiryDate'], DateTimeValidator::class];

        if ($this->getSection()->type !== Section::TYPE_SINGLE) {
            $rules[] = [['authorId'], 'required', 'on' => self::SCENARIO_LIVE];
        }

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getSupportedSites(): array
    {
        $section = $this->getSection();
        /** @var Site[] $allSites */
        $allSites = ArrayHelper::index(Craft::$app->getSites()->getAllSites(), 'id');
        $sites = [];

        // If the section is leaving it up to entries to decide which sites to be propagated to,
        // figure out which sites the entry is currently saved in
        if (
            ($this->duplicateOf->id ?? $this->id) &&
            $section->propagationMethod === Section::PROPAGATION_METHOD_CUSTOM
        ) {
            if ($this->id) {
                $currentSites = static::find()
                    ->anyStatus()
                    ->id($this->id)
                    ->siteId('*')
                    ->select('elements_sites.siteId')
                    ->drafts($this->getIsDraft())
                    ->revisions($this->getIsRevision())
                    ->column();
            } else {
                $currentSites = [];
            }

            // If this is being duplicated from another element (e.g. a draft), include any sites the source element is saved to as well
            if (!empty($this->duplicateOf->id)) {
                array_push($currentSites, ...static::find()
                    ->anyStatus()
                    ->id($this->duplicateOf->id)
                    ->siteId('*')
                    ->select('elements_sites.siteId')
                    ->drafts($this->duplicateOf->getIsDraft())
                    ->revisions($this->duplicateOf->getIsRevision())
                    ->column()
                );
            }

            $currentSites = array_flip($currentSites);
        }

        foreach ($section->getSiteSettings() as $siteSettings) {
            switch ($section->propagationMethod) {
                case Section::PROPAGATION_METHOD_NONE:
                    $include = $siteSettings->siteId == $this->siteId;
                    $propagate = true;
                    break;
                case Section::PROPAGATION_METHOD_SITE_GROUP:
                    $include = $allSites[$siteSettings->siteId]->groupId == $allSites[$this->siteId]->groupId;
                    $propagate = true;
                    break;
                case Section::PROPAGATION_METHOD_LANGUAGE:
                    $include = $allSites[$siteSettings->siteId]->language == $allSites[$this->siteId]->language;
                    $propagate = true;
                    break;
                case Section::PROPAGATION_METHOD_CUSTOM:
                    $include = true;
                    // Only actually propagate to this site if it's the current site, or the entry has been assigned
                    // a status for this site, or the entry already exists for this site
                    $propagate = (
                        $siteSettings->siteId == $this->siteId ||
                        $this->getEnabledForSite($siteSettings->siteId) !== null ||
                        isset($currentSites[$siteSettings->siteId])
                    );
                    break;
                default:
                    $include = $propagate = true;
                    break;
            }

            if ($include) {
                $sites[] = [
                    'siteId' => $siteSettings->siteId,
                    'propagate' => $propagate,
                    'enabledByDefault' => $siteSettings->enabledByDefault
                ];
            }
        }

        return $sites;
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function getCacheTags(): array
    {
        $tags = [
            "entryType:$this->typeId",
            "section:$this->sectionId",
        ];

        // Did the entry type just change?
        if ($this->typeId != $this->_oldTypeId) {
            $tags[] = "entryType:$this->_oldTypeId";
        }

        return $tags;
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException if [[siteId]] is not set to a site ID that the entry's section is enabled for
     */
    public function getUriFormat()
    {
        $sectionSiteSettings = $this->getSection()->getSiteSettings();

        if (!isset($sectionSiteSettings[$this->siteId])) {
            throw new InvalidConfigException('Entry’s section (' . $this->sectionId . ') is not enabled for site ' . $this->siteId);
        }

        return $sectionSiteSettings[$this->siteId]->uriFormat;
    }

    /**
     * @inheritdoc
     */
    protected function route()
    {
        // Make sure that the entry is actually live
        if (!$this->previewing && $this->getStatus() != self::STATUS_LIVE) {
            return null;
        }

        // Make sure the section is set to have URLs for this site
        $siteId = Craft::$app->getSites()->getCurrentSite()->id;
        $sectionSiteSettings = $this->getSection()->getSiteSettings();

        if (!isset($sectionSiteSettings[$siteId]) || !$sectionSiteSettings[$siteId]->hasUrls) {
            return null;
        }

        return [
            'templates/render', [
                'template' => $sectionSiteSettings[$siteId]->template,
                'variables' => [
                    'entry' => $this,
                ]
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    protected function previewTargets(): array
    {
        return array_map(function($previewTarget) {
            $previewTarget['label'] = Craft::t('site', $previewTarget['label']);
            return $previewTarget;
        }, $this->getSection()->previewTargets);
    }

    /**
     * Returns the reference string to this element.
     *
     * @return string|null
     */
    public function getRef()
    {
        return $this->getSection()->handle . '/' . $this->slug;
    }

    /**
     * @inheritdoc
     */
    public function getIsTitleTranslatable(): bool
    {
        return ($this->getType()->titleTranslationMethod !== Field::TRANSLATION_METHOD_NONE);
    }

    /**
     * @inheritdoc
     */
    public function getTitleTranslationDescription()
    {
        return ElementHelper::translationDescription($this->getType()->titleTranslationMethod);
    }

    /**
     * @inheritdoc
     */
    public function getTitleTranslationKey(): string
    {
        $type = $this->getType();
        return ElementHelper::translationKey($this, $type->titleTranslationMethod, $type->titleTranslationKeyFormat);
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout()
    {
        if (($fieldLayout = parent::getFieldLayout()) !== null) {
            return $fieldLayout;
        }
        try {
            $entryType = $this->getType();
        } catch (InvalidConfigException $e) {
            // The entry type was probably deleted
            return null;
        }
        return $entryType->getFieldLayout();
    }

    /**
     * Returns the entry's section.
     *
     * ---
     * ```php
     * $section = $entry->section;
     * ```
     * ```twig
     * {% set section = entry.section %}
     * ```
     *
     * @return Section
     * @throws InvalidConfigException if [[sectionId]] is missing or invalid
     */
    public function getSection(): Section
    {
        if ($this->sectionId === null) {
            throw new InvalidConfigException('Entry is missing its section ID');
        }

        if (($section = Craft::$app->getSections()->getSectionById($this->sectionId)) === null) {
            throw new InvalidConfigException('Invalid section ID: ' . $this->sectionId);
        }

        return $section;
    }

    /**
     * Returns the entry type.
     *
     * ---
     * ```php
     * $entryType = $entry->type;
     * ```
     * ```twig{1}
     * {% switch entry.type.handle %}
     *     {% case 'article' %}
     *         {% include "news/_article" %}
     *     {% case 'link' %}
     *         {% include "news/_link" %}
     * {% endswitch %}
     * ```
     *
     * @return EntryType
     * @throws InvalidConfigException if the section has no entry types
     */
    public function getType(): EntryType
    {
        if ($this->typeId === null) {
            throw new InvalidConfigException('Entry is missing its type ID');
        }

        $sectionEntryTypes = ArrayHelper::index($this->getSection()->getEntryTypes(), 'id');

        if ($this->typeId === null || !isset($sectionEntryTypes[$this->typeId])) {
            Craft::warning("Entry {$this->id} has an invalid entry type ID: {$this->typeId}");
            if (empty($sectionEntryTypes)) {
                throw new InvalidConfigException("Section {$this->sectionId} has no entry types");
            }
            return reset($sectionEntryTypes);
        }

        return $sectionEntryTypes[$this->typeId];
    }

    /**
     * Returns the entry's author.
     *
     * ---
     * ```php
     * $author = $entry->author;
     * ```
     * ```twig
     * <p>By {{ entry.author.name }}</p>
     * ```
     *
     * @return User|null
     * @throws InvalidConfigException if [[authorId]] is set but invalid
     */
    public function getAuthor()
    {
        if ($this->_author === null) {
            if ($this->authorId === null) {
                return null;
            }

            if (($this->_author = Craft::$app->getUsers()->getUserById($this->authorId)) === null) {
                // The author is probably soft-deleted. Just no author is set
                $this->_author = false;
            }
        }

        return $this->_author ?: null;
    }

    /**
     * Sets the entry's author.
     *
     * @param User|null $author
     */
    public function setAuthor(User $author = null)
    {
        $this->_author = $author;
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        $status = parent::getStatus();

        if ($status == self::STATUS_ENABLED && $this->postDate) {
            $currentTime = DateTimeHelper::currentTimeStamp();
            $postDate = $this->postDate->getTimestamp();
            $expiryDate = ($this->expiryDate ? $this->expiryDate->getTimestamp() : null);

            if ($postDate <= $currentTime && ($expiryDate === null || $expiryDate > $currentTime)) {
                return self::STATUS_LIVE;
            }

            if ($postDate > $currentTime) {
                return self::STATUS_PENDING;
            }

            return self::STATUS_EXPIRED;
        }

        return $status;
    }

    /**
     * @inheritdoc
     *
     * ---
     * ```php
     * $editable = $entry->isEditable;
     * ```
     * ```twig{1}
     * {% if entry.isEditable %}
     *     <a href="{{ entry.cpEditUrl }}">Edit</a>
     * {% endif %}
     * ```
     */
    public function getIsEditable(): bool
    {
        $section = $this->getSection();
        $userSession = Craft::$app->getUser();

        // Cover the basics
        if (
            !$userSession->checkPermission("editEntries:$section->uid") ||
            ($this->enabled && !$userSession->checkPermission("publishEntries:$section->uid"))
        ) {
            return false;
        }

        if ($section->type === Section::TYPE_SINGLE) {
            return true;
        }

        // Is this a new entry?
        if (!$this->id) {
            return $userSession->checkPermission("createEntries:$section->uid");
        }

        // Is this their own entry?
        if (!$this->authorId || $this->authorId == $userSession->getId()) {
            return true;
        }

        if (!$this->enabled) {
            return $userSession->checkPermission("editPeerEntries:$section->uid");
        }

        return $userSession->checkPermission("publishPeerEntries:$section->uid");
    }

    /**
     * @inheritdoc
     *
     * ---
     * ```php
     * $url = $entry->cpEditUrl;
     * ```
     * ```twig{2}
     * {% if entry.isEditable %}
     *     <a href="{{ entry.cpEditUrl }}">Edit</a>
     * {% endif %}
     * ```
     */
    public function getCpEditUrl()
    {
        $section = $this->getSection();

        // The slug *might* not be set if this is a Draft and they've deleted it for whatever reason
        $path = 'entries/' . $section->handle . '/' . $this->getSourceId() .
            ($this->slug && strpos($this->slug, '__') !== 0 ? '-' . $this->slug : '');

        $params = [];
        if (Craft::$app->getIsMultiSite()) {
            $params['site'] = $this->getSite()->handle;
        }

        return UrlHelper::cpUrl($path, $params);
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getGqlTypeName(): string
    {
        return static::gqlTypeNameByContext($this->getType());
    }

    /**
     * @inheritdoc
     */
    public function setEagerLoadedElements(string $handle, array $elements)
    {
        if ($handle === 'author') {
            $this->_author = $elements[0] ?? false;
        } else {
            parent::setEagerLoadedElements($handle, $elements);
        }
    }

    // Indexes, etc.
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'author':
                $author = $this->getAuthor();
                return $author ? Craft::$app->getView()->renderTemplate('_elements/element', ['element' => $author]) : '';

            case 'section':
                return Html::encode(Craft::t('site', $this->getSection()->name));

            case 'type':
                try {
                    return Craft::t('site', $this->getType()->name);
                } catch (InvalidConfigException $e) {
                    return Craft::t('app', 'Unknown');
                }

            case 'revisionNotes':
                /** @var Entry|null $revision */
                /** @var RevisionBehavior|null $behavior */
                if (
                    ($revision = $this->getCurrentRevision()) === null ||
                    ($behavior = $revision->getBehavior('revision')) === null
                ) {
                    return '';
                }
                return Html::encode($behavior->revisionNotes);

            case 'revisionCreator':
                /** @var Entry|null $revision */
                /** @var RevisionBehavior|null $behavior */
                if (
                    ($revision = $this->getCurrentRevision()) === null ||
                    ($behavior = $revision->getBehavior('revision')) === null ||
                    ($creator = $behavior->getCreator()) === null
                ) {
                    return '';
                }
                return Craft::$app->getView()->renderTemplate('_elements/element', ['element' => $creator]);
        }

        return parent::tableAttributeHtml($attribute);
    }

    /**
     * @inheritdoc
     */
    public function getEditorHtml(): string
    {
        $html = '';
        $view = Craft::$app->getView();

        // Show the Entry Type field?
        if ($this->id === null) {
            $entryTypes = $this->getSection()->getEntryTypes();

            if (count($entryTypes) > 1) {
                $entryTypeOptions = [];

                foreach ($entryTypes as $entryType) {
                    $entryTypeOptions[] = [
                        'label' => Craft::t('site', $entryType->name),
                        'value' => $entryType->id
                    ];
                }

                $html .= Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'selectField', [
                    [
                        'label' => Craft::t('app', 'Entry Type'),
                        'id' => 'entryType',
                        'value' => $this->typeId,
                        'options' => $entryTypeOptions,
                    ]
                ]);

                $typeInputId = $view->namespaceInputId('entryType');
                $js = <<<EOD
$('#{$typeInputId}').on('change', function(ev) {
    var \$typeInput = $(this),
        editor = \$typeInput.closest('.hud').data('elementEditor');
    if (editor) {
        editor.setElementAttribute('typeId', \$typeInput.val());
        editor.loadHud();
    }
});
EOD;
                $view->registerJs($js);
            }
        }

        // Render the custom fields
        $html .= parent::getEditorHtml();

        return $html;
    }

    /**
     * Updates the entry's title, if its entry type has a dynamic title format.
     *
     * @since 3.0.3
     */
    public function updateTitle()
    {
        $entryType = $this->getType();
        if (!$entryType->hasTitleField) {
            // Make sure that the locale has been loaded in case the title format has any Date/Time fields
            Craft::$app->getLocale();
            // Set Craft to the entry's site's language, in case the title format has any static translations
            $language = Craft::$app->language;
            Craft::$app->language = $this->getSite()->language;
            $title = Craft::$app->getView()->renderObjectTemplate($entryType->titleFormat, $this);
            if ($title !== '') {
                $this->title = $title;
            }
            Craft::$app->language = $language;
        }
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function beforeValidate()
    {
        if (!$this->authorId && $this->getSection()->type !== Section::TYPE_SINGLE) {
            $this->authorId = Craft::$app->getUser()->getId();
        }

        return parent::beforeValidate();
    }

    /**
     * @inheritdoc
     * @throws Exception if reasons
     */
    public function beforeSave(bool $isNew): bool
    {
        $section = $this->getSection();

        // Verify that the section supports this site
        $sectionSiteSettings = $section->getSiteSettings();
        if (!isset($sectionSiteSettings[$this->siteId])) {
            throw new UnsupportedSiteException($this, $this->siteId, "The section '{$section->name}' is not enabled for the site '{$this->siteId}'");
        }

        // Make sure the entry has at least one revision if the section has versioning enabled
        if ($this->_shouldSaveRevision()) {
            $hasRevisions = self::find()
                ->revisionOf($this)
                ->siteId('*')
                ->anyStatus()
                ->exists();
            if (!$hasRevisions) {
                $currentEntry = self::find()
                    ->id($this->id)
                    ->siteId('*')
                    ->anyStatus()
                    ->one();
                $revisionNotes = 'Revision from ' . Craft::$app->getFormatter()->asDatetime($currentEntry->dateUpdated);
                Craft::$app->getRevisions()->createRevision($currentEntry, $currentEntry->authorId, $revisionNotes);
            }
        }

        // Set the structure ID for Element::attributes() and afterSave()
        if ($section->type === Section::TYPE_STRUCTURE) {
            $this->structureId = $section->structureId;
        }

        // Has the entry been assigned to a new parent?
        if ($this->_hasNewParent()) {
            if ($this->newParentId) {
                $parentEntry = Craft::$app->getEntries()->getEntryById($this->newParentId, '*', [
                    'preferSites' => [$this->siteId],
                ]);

                if (!$parentEntry) {
                    throw new Exception('Invalid entry ID: ' . $this->newParentId);
                }
            } else {
                $parentEntry = null;
            }

            $this->setParent($parentEntry);
        }

        // Section type-specific stuff
        if ($section->type == Section::TYPE_SINGLE) {
            $this->authorId = null;
            $this->expiryDate = null;
        }

        $this->updateTitle();

        if ($this->enabled && !$this->postDate) {
            // Default the post date to the current date/time
            $this->postDate = new \DateTime();
            // ...without the seconds
            $this->postDate->setTimestamp($this->postDate->getTimestamp() - ($this->postDate->getTimestamp() % 60));
        }

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     * @throws Exception if reasons
     */
    public function afterSave(bool $isNew)
    {
        if (!$this->propagating) {
            $section = $this->getSection();

            // Get the entry record
            if (!$isNew) {
                $record = EntryRecord::findOne($this->id);

                if (!$record) {
                    throw new Exception('Invalid entry ID: ' . $this->id);
                }
            } else {
                $record = new EntryRecord();
                $record->id = (int)$this->id;
            }

            $record->sectionId = (int)$this->sectionId;
            $record->typeId = (int)$this->typeId;
            $record->authorId = (int)$this->authorId ?: null;
            $record->postDate = $this->postDate;
            $record->expiryDate = $this->expiryDate;

            // Capture the dirty attributes from the record
            $dirtyAttributes = array_keys($record->getDirtyAttributes());

            $record->save(false);

            if (!$this->duplicateOf && $section->type == Section::TYPE_STRUCTURE) {
                // Has the parent changed?
                if ($this->_hasNewParent()) {
                    $mode = $isNew ? Structures::MODE_INSERT : Structures::MODE_AUTO;
                    if (!$this->newParentId) {
                        Craft::$app->getStructures()->appendToRoot($this->structureId, $this, $mode);
                    } else {
                        Craft::$app->getStructures()->append($this->structureId, $this, $this->getParent(), $mode);
                    }
                }

                // Update the entry's descendants, who may be using this entry's URI in their own URIs
                if (!$isNew) {
                    Craft::$app->getElements()->updateDescendantSlugsAndUris($this, true, true);
                }
            }

            $this->setDirtyAttributes($dirtyAttributes);
        }

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function afterPropagate(bool $isNew)
    {
        parent::afterPropagate($isNew);

        // Save a new revision?
        if ($this->_shouldSaveRevision()) {
            Craft::$app->getRevisions()->createRevision($this, $this->revisionCreatorId, $this->revisionNotes);
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        $data = [
            'deletedWithEntryType' => $this->deletedWithEntryType,
            'parentId' => null,
        ];

        if ($this->structureId) {
            // Remember the parent ID, in case the entry needs to be restored later
            $parentId = $this->getAncestors(1)
                ->anyStatus()
                ->select(['elements.id'])
                ->scalar();
            if ($parentId) {
                $data['parentId'] = $parentId;
            }
        }

        Db::update(Table::ENTRIES, $data, [
            'id' => $this->id,
        ], [], false);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterRestore()
    {
        $section = $this->getSection();
        if ($section->type === Section::TYPE_STRUCTURE) {
            // Add the entry back into its structure
            $parent = self::find()
                ->structureId($section->structureId)
                ->innerJoin(['j' => Table::ENTRIES], '[[j.parentId]] = [[elements.id]]')
                ->andWhere(['j.id' => $this->id])
                ->one();

            if (!$parent) {
                Craft::$app->getStructures()->appendToRoot($section->structureId, $this);
            } else {
                Craft::$app->getStructures()->append($section->structureId, $this, $parent);
            }
        }

        parent::afterRestore();
    }

    /**
     * @inheritdoc
     */
    public function afterMoveInStructure(int $structureId)
    {
        // Was the entry moved within its section's structure?
        $section = $this->getSection();

        if ($section->type == Section::TYPE_STRUCTURE && $section->structureId == $structureId) {
            Craft::$app->getElements()->updateElementSlugAndUri($this, true, true, true);
        }

        parent::afterMoveInStructure($structureId);
    }

    /**
     * Returns whether the entry has been assigned a new parent entry.
     *
     * @return bool
     * @see beforeSave()
     * @see afterSave()
     */
    private function _hasNewParent(): bool
    {
        if ($this->_hasNewParent !== null) {
            return $this->_hasNewParent;
        }

        return $this->_hasNewParent = $this->_checkForNewParent();
    }

    /**
     * Checks if the entry has been assigned a new parent entry.
     *
     * @return bool
     * @see _hasNewParent()
     */
    private function _checkForNewParent(): bool
    {
        // Make sure this is a Structure section
        if ($this->getSection()->type != Section::TYPE_STRUCTURE) {
            return false;
        }

        // Is it a brand new entry?
        if ($this->id === null) {
            return true;
        }

        // Was a new parent ID actually submitted?
        if ($this->newParentId === null) {
            return false;
        }

        // Is it set to the top level now, but it hadn't been before?
        if (!$this->newParentId && $this->level != 1) {
            return true;
        }

        // Is it set to be under a parent now, but didn't have one before?
        if ($this->newParentId && $this->level == 1) {
            return true;
        }

        // Is the parentId set to a different entry ID than its previous parent?
        $oldParentQuery = self::find();
        $oldParentQuery->ancestorOf($this);
        $oldParentQuery->ancestorDist(1);
        $oldParentQuery->siteId($this->siteId);
        $oldParentQuery->anyStatus();
        $oldParentQuery->select('elements.id');
        $oldParentId = $oldParentQuery->scalar();

        return $this->newParentId != $oldParentId;
    }

    /**
     * Returns whether the entry should be saving revisions on save.
     *
     * @return bool
     */
    private function _shouldSaveRevision(): bool
    {
        return (
            $this->id &&
            !$this->propagating &&
            !$this->resaving &&
            !$this->getIsDraft() &&
            !$this->getIsRevision() &&
            $this->getSection()->enableVersioning
        );
    }

    /**
     * Get the GraphQL identifier by context.
     *
     * @param EntryType $context
     * @return string
     */
    private static function _getGqlIdentifierByContext(EntryType $context): string
    {
        $gqlIdentifier = $context->getSection()->handle . '_' . $context->handle;
        return $gqlIdentifier;
    }
}
