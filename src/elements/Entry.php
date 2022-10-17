<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\behaviors\DraftBehavior;
use craft\behaviors\RevisionBehavior;
use craft\controllers\ElementIndexesController;
use craft\db\Table;
use craft\elements\actions\Delete;
use craft\elements\actions\DeleteForSite;
use craft\elements\actions\Duplicate;
use craft\elements\actions\Edit;
use craft\elements\actions\NewChild;
use craft\elements\actions\NewSiblingAfter;
use craft\elements\actions\NewSiblingBefore;
use craft\elements\actions\Restore;
use craft\elements\actions\SetStatus;
use craft\elements\actions\View;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\EntryQuery;
use craft\errors\UnsupportedSiteException;
use craft\events\DefineEntryTypesEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\EntryType;
use craft\models\Section;
use craft\models\Site;
use craft\records\Entry as EntryRecord;
use craft\services\Structures;
use craft\validators\DateCompareValidator;
use craft\validators\DateTimeValidator;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\Expression;

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
     * @event DefineEntryTypesEvent The event that is triggered when defining the available entry types for the entry
     *
     * @see getAvailableEntryTypes()
     * @since 3.6.0
     */
    const EVENT_DEFINE_ENTRY_TYPES = 'defineEntryTypes';

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
            self::STATUS_DISABLED => Craft::t('app', 'Disabled'),
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
                    'editable' => $editable,
                ],
                'defaultSort' => ['postDate', 'desc'],
            ],
        ];

        if (!empty($singleSectionIds)) {
            $sources[] = [
                'key' => 'singles',
                'label' => Craft::t('app', 'Singles'),
                'criteria' => [
                    'sectionId' => $singleSectionIds,
                    'editable' => $editable,
                ],
                'defaultSort' => ['title', 'asc'],
            ];
        }

        $sectionTypes = [
            Section::TYPE_CHANNEL => Craft::t('app', 'Channels'),
            Section::TYPE_STRUCTURE => Craft::t('app', 'Structures'),
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
                            'handle' => $section->handle,
                        ],
                        'criteria' => [
                            'sectionId' => $section->id,
                            'editable' => $editable,
                        ],
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
        } elseif ($source === 'singles') {
            $sections = Craft::$app->getSections()->getSectionsByType(Section::TYPE_SINGLE);
        } elseif (
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
                } elseif (preg_match('/^section:(.+)$/', $source, $matches)) {
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

            if ($source === '*') {
                // Delete
                $actions[] = Delete::class;
            } elseif ($source !== 'singles') {
                // Channel/Structure-only actions
                $section = $sections[0];

                if (
                    $section->type == Section::TYPE_STRUCTURE &&
                    $userSession->checkPermission('createEntries:' . $section->uid)
                ) {
                    $newEntryUrl = 'entries/' . $section->handle . '/new';

                    if (Craft::$app->getIsMultiSite()) {
                        $newEntryUrl .= '?site=' . $site->handle;
                    }

                    $actions[] = $elementsService->createAction([
                        'type' => NewSiblingBefore::class,
                        'label' => Craft::t('app', 'Create a new entry before'),
                        'newSiblingUrl' => $newEntryUrl,
                    ]);

                    $actions[] = $elementsService->createAction([
                        'type' => NewSiblingAfter::class,
                        'label' => Craft::t('app', 'Create a new entry after'),
                        'newSiblingUrl' => $newEntryUrl,
                    ]);

                    if ($section->maxLevels != 1) {
                        $actions[] = $elementsService->createAction([
                            'type' => NewChild::class,
                            'label' => Craft::t('app', 'Create a new child entry'),
                            'maxLevels' => $section->maxLevels,
                            'newChildUrl' => $newEntryUrl,
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
                $actions[] = Delete::class;

                if ($userSession->checkPermission("deleteEntries:$section->uid")) {
                    if (
                        $section->type === Section::TYPE_STRUCTURE &&
                        $section->maxLevels != 1 &&
                        $userSession->checkPermission("deletePeerEntries:$section->uid")
                    ) {
                        $actions[] = [
                            'type' => Delete::class,
                            'withDescendants' => true,
                        ];
                    }

                    if ($section->propagationMethod === Section::PROPAGATION_METHOD_CUSTOM && $section->getHasMultiSiteEntries()) {
                        $actions[] = DeleteForSite::class;
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
            [
                'label' => Craft::t('app', 'Post Date'),
                'orderBy' => function(int $dir) {
                    if ($dir === SORT_ASC) {
                        if (Craft::$app->getDb()->getIsMysql()) {
                            return new Expression('[[postDate]] IS NOT NULL DESC, [[postDate]] ASC');
                        } else {
                            return new Expression('[[postDate]] ASC NULLS LAST');
                        }
                    }
                    if (Craft::$app->getDb()->getIsMysql()) {
                        return new Expression('[[postDate]] IS NULL DESC, [[postDate]] DESC');
                    } else {
                        return new Expression('[[postDate]] DESC NULLS FIRST');
                    }
                },
                'attribute' => 'postDate',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'Expiry Date'),
                'orderBy' => 'expiryDate',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'Date Created'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated',
                'defaultDir' => 'desc',
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
            'title' => ['label' => Craft::t('app', 'Entry')],
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
            'drafts' => ['label' => Craft::t('app', 'Drafts')],
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
            $attributes[] = 'author';
        }

        $attributes[] = 'link';

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle)
    {
        if ($handle === 'author') {
            $sourceElementsWithAuthors = array_filter($sourceElements, function(Entry $entry) {
                return $entry->authorId !== null;
            });

            $map = array_map(function(Entry $entry) {
                return [
                    'source' => $entry->id,
                    'target' => $entry->authorId,
                ];
            }, $sourceElementsWithAuthors);

            return [
                'elementType' => User::class,
                'map' => $map,
                'criteria' => [
                    'status' => null,
                ],
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
                $elementQuery->andWith(['author', ['status' => null]]);
                break;
            case 'revisionNotes':
                $elementQuery->andWith('currentRevision');
                break;
            case 'revisionCreator':
                $elementQuery->andWith('currentRevision.revisionCreator');
                break;
            case 'drafts':
                $elementQuery->andWith(['drafts', ['status' => null, 'orderBy' => ['dateUpdated' => SORT_DESC]]]);
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
     *   {{ entry.expiryDate|date('short') }}
     * {% endif %}
     * ```
     */
    public $expiryDate;

    /**
     * @var int|false|null New parent ID
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
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'postDate' => Craft::t('app', 'Post Date'),
            'expiryDate' => Craft::t('app', 'Expiry Date'),
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['sectionId', 'typeId', 'authorId'], 'number', 'integerOnly' => true];
        $rules[] = [['postDate', 'expiryDate'], DateTimeValidator::class];

        $rules[] = [
            ['postDate'],
            DateCompareValidator::class,
            'operator' => '<',
            'compareAttribute' => 'expiryDate',
            'when' => function() {
                return $this->postDate && $this->expiryDate;
            },
            'on' => self::SCENARIO_LIVE,
        ];

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
        $allSites = ArrayHelper::index(Craft::$app->getSites()->getAllSites(true), 'id');
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
                    ->site('*')
                    ->select('elements_sites.siteId')
                    ->drafts(null)
                    ->provisionalDrafts(null)
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
                    ->site('*')
                    ->select('elements_sites.siteId')
                    ->drafts(null)
                    ->provisionalDrafts(null)
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
                    'enabledByDefault' => $siteSettings->enabledByDefault,
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
                'template' => (string)$sectionSiteSettings[$siteId]->template,
                'variables' => [
                    'entry' => $this,
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function uiLabel(): ?string
    {
        if ($this->title === null || trim($this->title) === '') {
            return Craft::t('app', 'Untitled entry');
        }

        return null;
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
     * Returns the available entry types for the entry.
     *
     * @return EntryType[]
     * @throws InvalidConfigException
     * @since 3.6.0
     */
    public function getAvailableEntryTypes(): array
    {
        $entryTypes = $this->getSection()->getEntryTypes();

        // Fire a 'defineEntryTypes' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_ENTRY_TYPES)) {
            $event = new DefineEntryTypesEvent([
                'entryTypes' => $entryTypes,
            ]);
            $this->trigger(self::EVENT_DEFINE_ENTRY_TYPES, $event);
            $entryTypes = $event->entryTypes;
        }

        return $entryTypes;
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
     *   {% case 'article' %}
     *     {% include "news/_article" %}
     *   {% case 'link' %}
     *     {% include "news/_link" %}
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
        $this->authorId = $author->id;
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
     *   <a href="{{ entry.cpEditUrl }}">Edit</a>
     * {% endif %}
     * ```
     */
    protected function isEditable(): bool
    {
        $section = $this->getSection();
        $userSession = Craft::$app->getUser();

        // Cover the basics
        if (!$userSession->checkPermission("editEntries:$section->uid")) {
            return false;
        }

        $isDraft = $this->getIsDraft() && !$this->getIsCanonical();
        $userId = $userSession->getId();

        if ($isDraft) {
            /** @var DraftBehavior $behavior */
            $behavior = $this->getBehavior('draft');
            if ($behavior->creatorId != $userId && !$userSession->checkPermission("editPeerEntryDrafts:$section->uid")) {
                return false;
            }
        } elseif ($this->enabled && !$userSession->checkPermission("publishEntries:$section->uid")) {
            return false;
        }

        if ($section->type === Section::TYPE_SINGLE) {
            return true;
        }

        // Is this a new entry?
        if (!$isDraft && !$this->id) {
            return $userSession->checkPermission("createEntries:$section->uid");
        }

        // Is this another author's entry?
        if ($this->authorId && $this->authorId != $userId && !$userSession->checkPermission("editPeerEntries:$section->uid")) {
            return false;
        }

        if (!$isDraft && $this->enabled && !$userSession->checkPermission("publishPeerEntries:$section->uid")) {
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function isDeletable(): bool
    {
        $section = $this->getSection();
        $userSession = Craft::$app->getUser();
        $userId = $userSession->getId();

        if ($this->getIsDraft()) {
            /** @var Entry|DraftBehavior $this */
            return $this->creatorId == $userId || $userSession->checkPermission("deletePeerEntryDrafts:$section->uid");
        }

        if ($section->type === Section::TYPE_SINGLE) {
            return false;
        }

        return (
            $userSession->checkPermission("deleteEntries:$section->uid") &&
            ($this->authorId == $userId || $userSession->checkPermission("deletePeerEntries:$section->uid"))
        );
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
     *   <a href="{{ entry.cpEditUrl }}">Edit</a>
     * {% endif %}
     * ```
     */
    protected function cpEditUrl(): ?string
    {
        $section = $this->getSection();

        // The slug *might* not be set if this is a Draft and they've deleted it for whatever reason
        $path = 'entries/' . $section->handle . '/' . $this->getCanonicalId() .
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
                return $author ? Cp::elementHtml($author) : '';

            case 'section':
                return Html::encode(Craft::t('site', $this->getSection()->name));

            case 'type':
                try {
                    return Html::encode(Craft::t('site', $this->getType()->name));
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
                return Cp::elementHtml($creator);

            case 'drafts':
                if (!$this->hasEagerLoadedElements('drafts')) {
                    return '';
                }

                $drafts = $this->getEagerLoadedElements('drafts');

                foreach ($drafts as $draft) {
                    /** @var ElementInterface|DraftBehavior $draft */
                    $draft->setUiLabel($draft->draftName);
                }

                return Cp::elementPreviewHtml($drafts, Cp::ELEMENT_SIZE_SMALL, true, false, true, false);
        }

        return parent::tableAttributeHtml($attribute);
    }

    /**
     * @inheritdoc
     */
    public function metaFieldsHtml(): string
    {
        $fields = [];
        $view = Craft::$app->getView();
        $section = $this->getSection();

        if ($section->type !== Section::TYPE_SINGLE) {
            $entryTypes = $this->getAvailableEntryTypes();

            if (count($entryTypes) > 1) {
                $entryTypeOptions = [];
                $fieldLayoutIds = [];

                foreach ($entryTypes as $entryType) {
                    $entryTypeOptions[] = [
                        'label' => Craft::t('site', $entryType->name),
                        'value' => $entryType->id,
                    ];
                    $fieldLayoutIds["type-$entryType->id"] = $entryType->fieldLayoutId;
                }

                $fields[] = Cp::selectFieldHtml([
                    'label' => Craft::t('app', 'Entry Type'),
                    'id' => 'entryType',
                    'value' => $this->typeId,
                    'options' => $entryTypeOptions,
                ]);

                $typeInputId = $view->namespaceInputId('entryType');
                $fieldLayoutIdsJson = Json::encode($fieldLayoutIds);
                $js = <<<EOD
(() => {
    const \$typeInput = $('#$typeInputId');
    const editor = \$typeInput.closest('.element-editor').data('elementEditor');
    const fieldLayoutIds = $fieldLayoutIdsJson;
    if (editor) {
        \$typeInput.on('change', function(ev) {
            editor.setElementAttribute('typeId', \$typeInput.val());
            editor.setElementAttribute('fieldLayoutId', fieldLayoutIds[`type-\${\$typeInput.val()}`]);
            editor.load({}, false);
        });
    }
})();
EOD;
                $view->registerJs($js);
            }
        }

        $fields[] = $this->slugFieldHtml();

        if ($section->type !== Section::TYPE_SINGLE) {
            if (
                Craft::$app->getEdition() === Craft::Pro &&
                Craft::$app->getUser()->checkPermission("editPeerEntries:$section->uid")
            ) {
                $author = $this->getAuthor();
                $fields[] = Cp::elementSelectFieldHtml([
                    'label' => Craft::t('app', 'Author'),
                    'id' => 'authorId',
                    'name' => 'authorId',
                    'elementType' => User::class,
                    'selectionLabel' => Craft::t('app', 'Choose'),
                    'criteria' => [
                        'can' => "editEntries:$section->uid",
                    ],
                    'single' => true,
                    'elements' => $author ? [$author] : null,
                ]);
            }

            $fields[] = Cp::dateTimeFieldHtml([
                'label' => Craft::t('app', 'Post Date'),
                'id' => 'postDate',
                'name' => 'postDate',
                'value' => $this->postDate,
                'errors' => $this->getErrors('postDate'),
            ]);

            $fields[] = Cp::dateTimeFieldHtml([
                'label' => Craft::t('app', 'Expiry Date'),
                'id' => 'expiryDate',
                'name' => 'expiryDate',
                'value' => $this->expiryDate,
                'errors' => $this->getErrors('expiryDate'),
            ]);
        }

        $fields[] = parent::metaFieldsHtml();

        return implode('', $fields);
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
                ->site('*')
                ->anyStatus()
                ->exists();
            if (!$hasRevisions) {
                $currentEntry = self::find()
                    ->id($this->id)
                    ->site('*')
                    ->anyStatus()
                    ->one();

                // May be null if the entry is currently stored as an unpublished draft
                if ($currentEntry) {
                    $revisionNotes = 'Revision from ' . Craft::$app->getFormatter()->asDatetime($currentEntry->dateUpdated);
                    Craft::$app->getRevisions()->createRevision($currentEntry, $currentEntry->authorId, $revisionNotes);
                }
            }
        }

        // Set the structure ID for Element::attributes() and afterSave()
        if ($section->type === Section::TYPE_STRUCTURE) {
            $this->structureId = $section->structureId;

            if (!$this->duplicateOf) {
                // Has the entry been assigned to a new parent?
                if ($this->_hasNewParent()) {
                    if ($this->newParentId) {
                        $parentEntry = Craft::$app->getEntries()->getEntryById($this->newParentId, '*', [
                            'preferSites' => [$this->siteId],
                            'drafts' => null,
                            'draftOf' => false,
                        ]);

                        if (!$parentEntry) {
                            throw new \Exception('Invalid entry ID: ' . $this->newParentId);
                        }
                    } else {
                        $parentEntry = null;
                    }

                    $this->setParent($parentEntry);
                }
            }
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
            // ...unless an expiry date is set in the past
            if ($this->expiryDate && $this->postDate >= $this->expiryDate) {
                $this->postDate = (clone $this->expiryDate)->modify('-1 day');
            }
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

            if ($this->getIsCanonical() && $section->type == Section::TYPE_STRUCTURE) {
                // Has the parent changed?
                if ($this->_hasNewParent()) {
                    $this->_placeInStructure($isNew, $section);
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

    private function _placeInStructure(bool $isNew, Section $section): void
    {
        // If this is a provisional draft and its new parent matches the canonical entry’s, just drop it from the structure
        if ($this->isProvisionalDraft) {
            $canonicalParentId = Entry::find()
                ->select(['elements.id'])
                ->ancestorOf($this->getCanonicalId())
                ->ancestorDist(1)
                ->anyStatus()
                ->scalar();

            if ($this->newParentId == $canonicalParentId) {
                Craft::$app->getStructures()->remove($this->structureId, $this);
                return;
            }
        }

        $mode = $isNew ? Structures::MODE_INSERT : Structures::MODE_AUTO;

        if (!$this->newParentId) {
            if ($section->defaultPlacement === Section::DEFAULT_PLACEMENT_BEGINNING) {
                Craft::$app->getStructures()->prependToRoot($this->structureId, $this, $mode);
            } else {
                Craft::$app->getStructures()->appendToRoot($this->structureId, $this, $mode);
            }
        } else {
            if ($section->defaultPlacement === Section::DEFAULT_PLACEMENT_BEGINNING) {
                Craft::$app->getStructures()->prepend($this->structureId, $this, $this->getParent(), $mode);
            } else {
                Craft::$app->getStructures()->append($this->structureId, $this, $this->getParent(), $mode);
            }
        }
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

            // If this is the canonical entry, update its drafts
            if ($this->getIsCanonical()) {
                $drafts = static::find()
                    ->draftOf($this)
                    ->anyStatus()
                    ->site('*')
                    ->unique()
                    ->all();
                $structuresService = Craft::$app->getStructures();
                $lastElement = $this;

                foreach ($drafts as $draft) {
                    $structuresService->moveAfter($section->structureId, $draft, $lastElement);
                    $lastElement = $draft;
                }
            }
        }

        parent::afterMoveInStructure($structureId);
    }

    /**
     * Returns whether the entry has been assigned a new parent.
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
     * Checks if the entry has been assigned a new parent.
     *
     * @return bool
     * @see _hasNewParent()
     */
    private function _checkForNewParent(): bool
    {
        // Make sure this is a Structure section, and that it’s either a canonical entry or provisional drafT
        if (!(
            $this->getSection()->type === Section::TYPE_STRUCTURE &&
            ($this->getIsCanonical() || $this->isProvisionalDraft)
        )) {
            return false;
        }

        // Is it a brand new (non-provisional) entry?
        if ($this->id === null && !$this->isProvisionalDraft) {
            return true;
        }

        // Was a new parent ID actually submitted?
        if ($this->newParentId === null) {
            return false;
        }

        // If this is a provisional draft, but doesn't actually exist in the structure yet, check based on the canonical entry
        if ($this->isProvisionalDraft && !$this->lft) {
            $entry = $this->getCanonical(true);
        } else {
            $entry = $this;
        }

        // Is it set to the top level now, but it hadn't been before?
        if (!$this->newParentId && $entry->level != 1) {
            return true;
        }

        // Is it set to be under a parent now, but didn't have one before?
        if ($this->newParentId && $entry->level == 1) {
            return true;
        }

        // Is the parentId set to a different entry ID than its previous parent?
        $oldParentId = self::find()
            ->ancestorOf($entry)
            ->ancestorDist(1)
            ->siteId($entry->siteId)
            ->anyStatus()
            ->select('elements.id')
            ->scalar();

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
