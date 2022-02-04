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
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\conditions\entries\EntryCondition;
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
use craft\helpers\UrlHelper;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\models\Site;
use craft\records\Entry as EntryRecord;
use craft\services\Structures;
use craft\validators\DateCompareValidator;
use craft\validators\DateTimeValidator;
use DateTime;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\Expression;

/**
 * Entry represents an entry element.
 *
 * @property int $typeId the entry type’s ID
 * @property int|null $authorId the entry author’s ID
 * @property EntryType $type the entry type
 * @property Section $section the entry's section
 * @property User|null $author the entry's author
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Entry extends Element
{
    public const STATUS_LIVE = 'live';
    public const STATUS_PENDING = 'pending';
    public const STATUS_EXPIRED = 'expired';

    /**
     * @event DefineEntryTypesEvent The event that is triggered when defining the available entry types for the entry
     *
     * @see getAvailableEntryTypes()
     * @since 3.6.0
     */
    public const EVENT_DEFINE_ENTRY_TYPES = 'defineEntryTypes';

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
    public static function refHandle(): ?string
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
     * @return EntryCondition
     */
    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(EntryCondition::class, [static::class]);
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context): array
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

        $user = Craft::$app->getUser()->getIdentity();

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
                        $source['structureEditable'] = $user->can("saveEntries:$section->uid");
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
    protected static function defineActions(string $source): array
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
            $user = Craft::$app->getUser()->getIdentity();
            $canSetStatus = true;
            $canEdit = false;

            foreach ($sections as $section) {
                $canSaveEntries = $user->can("saveEntries:$section->uid");

                // Only show the Set Status action if we're sure they can make changes in all the sections
                if (
                    !$canSaveEntries ||
                    ($section->type !== Section::TYPE_SINGLE && !$user->can("savePeerEntries:$section->uid"))
                ) {
                    $canSetStatus = false;
                }

                // Show the Edit action if they can publish changes to *any* of the sections
                // (the trigger will disable itself for entries that aren't editable)
                if ($canSaveEntries) {
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
            } else if ($source !== 'singles') {
                // Channel/Structure-only actions
                $section = $sections[0];

                if (
                    $section->type == Section::TYPE_STRUCTURE &&
                    $user->can('createEntries:' . $section->uid)
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
                    $user->can("createEntries:$section->uid") &&
                    $user->can("saveEntries:$section->uid")
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

                if ($user->can("deleteEntries:$section->uid")) {
                    if (
                        $section->type === Section::TYPE_STRUCTURE &&
                        $section->maxLevels != 1 &&
                        $user->can("deletePeerEntries:$section->uid")
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
            $sourceElementsWithAuthors = array_filter($sourceElements, function(self $entry) {
                return $entry->getAuthorId() !== null;
            });

            $map = array_map(function(self $entry) {
                return [
                    'source' => $entry->id,
                    'target' => $entry->getAuthorId()
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
    protected static function prepElementQueryForTableAttribute(ElementQueryInterface $elementQuery, string $attribute): void
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
    public ?int $sectionId = null;

    /**
     * @var DateTime|null Post date
     * ---
     * ```php
     * echo Craft::$app->formatter->asDate($entry->postDate, 'short');
     * ```
     * ```twig
     * {{ entry.postDate|date('short') }}
     * ```
     */
    public ?DateTime $postDate = null;

    /**
     * @var DateTime|null Expiry date
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
    public ?DateTime $expiryDate = null;

    /**
     * @var bool Whether the entry was deleted along with its entry type
     * @see beforeDelete()
     * @internal
     */
    public bool $deletedWithEntryType = false;

    /**
     * @var int|null Author ID
     * @see getAuthorId()
     * @see setAuthorId()
     */
    public ?int $_authorId = null;

    /**
     * @var User|null|false
     * @see getAuthor()
     * @see setAuthor()
     */
    private $_author;

    /**
     * @var int|null Type ID
     * @see getType()
     */
    private ?int $_typeId = null;

    /**
     * @var int|null
     */
    private ?int $_oldTypeId = null;

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function init(): void
    {
        parent::init();
        $this->_oldTypeId = $this->_typeId;
    }

    /**
     * @inheritdoc
     */
    public function attributes(): array
    {
        $names = parent::attributes();
        $names[] = 'authorId';
        $names[] = 'typeId';
        return $names;
    }

    /**
     * @inheritdoc
     */
    public function extraFields(): array
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
    public function attributeLabels(): array
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

        if ($this->sectionId) {
            $section = $this->getSection();

            if ($section->type !== Section::TYPE_SINGLE) {
                $rules[] = [['authorId'], 'required', 'on' => self::SCENARIO_LIVE];
            }
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
                    ->status(null)
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
                    ->status(null)
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
            sprintf('entryType:%s', $this->getTypeId()),
            "section:$this->sectionId",
        ];

        // Did the entry type just change?
        if ($this->getTypeId() !== $this->_oldTypeId) {
            $tags[] = "entryType:$this->_oldTypeId";
        }

        return $tags;
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException if [[siteId]] is not set to a site ID that the entry's section is enabled for
     */
    public function getUriFormat(): ?string
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
        if (!isset($this->title) || trim($this->title) === '') {
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
    public function getRef(): ?string
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
    public function getTitleTranslationDescription(): ?string
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
    public function getFieldLayout(): ?FieldLayout
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
        if (!isset($this->sectionId)) {
            throw new InvalidConfigException('Entry is missing its section ID');
        }

        if (($section = Craft::$app->getSections()->getSectionById($this->sectionId)) === null) {
            throw new InvalidConfigException('Invalid section ID: ' . $this->sectionId);
        }

        return $section;
    }

    /**
     * Returns the entry type ID.
     *
     * @return int
     * @since 4.0.0
     */
    public function getTypeId(): int
    {
        return $this->getType()->id;
    }

    /**
     * Sets the entry type ID.
     *
     * @param int $typeId
     * @since 4.0.0
     */
    public function setTypeId(int $typeId): void
    {
        $this->_typeId = $typeId;
        $this->fieldLayoutId = null;
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
        if (!isset($this->_typeId)) {
            // Default to the section's first entry type
            $entryTypes = $this->getAvailableEntryTypes();
            if (!$entryTypes) {
                throw new InvalidConfigException('Entry is missing its type ID');
            }
            $this->_typeId = $entryTypes[0]->id;
        }

        $sectionEntryTypes = ArrayHelper::index($this->getSection()->getEntryTypes(), 'id');

        if (!isset($sectionEntryTypes[$this->_typeId])) {
            Craft::warning("Entry $this->id has an invalid entry type ID: $this->_typeId");
            if (empty($sectionEntryTypes)) {
                throw new InvalidConfigException("Section $this->sectionId has no entry types");
            }
            return reset($sectionEntryTypes);
        }

        return $sectionEntryTypes[$this->_typeId];
    }

    /**
     * Returns the entry author ID.
     *
     * @return int|null
     * @since 4.0.0
     */
    public function getAuthorId(): ?int
    {
        return $this->_authorId;
    }

    /**
     * Sets the entry author ID.
     *
     * @param int|int[]|null $authorId
     * @since 4.0.0
     */
    public function setAuthorId($authorId): void
    {
        if (is_array($authorId)) {
            $this->_authorId = reset($authorId) ?: null;
        } else {
            $this->_authorId = $authorId;
        }

        $this->_author = null;
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
    public function getAuthor(): ?User
    {
        if (!isset($this->_author)) {
            if (!$this->getAuthorId()) {
                return null;
            }

            if (($this->_author = Craft::$app->getUsers()->getUserById($this->getAuthorId())) === null) {
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
    public function setAuthor(?User $author = null): void
    {
        $this->_author = $author;
        $this->setAuthorId($author->id);
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): ?string
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
     */
    public function createAnother(): ?ElementInterface
    {
        $section = $this->getSection();

        /** @var self $entry */
        $entry = Craft::createObject([
            'class' => self::class,
            'sectionId' => $this->sectionId,
            'typeId' => $this->typeId,
            'siteId' => $this->siteId,
        ]);

        // Set the default status based on the section's settings
        /** @var Section_SiteSettings $siteSettings */
        $siteSettings = ArrayHelper::firstWhere($section->getSiteSettings(), 'siteId', $this->siteId);
        $enabled = $siteSettings->enabledByDefault;

        if (Craft::$app->getIsMultiSite() && count($entry->getSupportedSites()) > 1) {
            $entry->enabled = true;
            $entry->setEnabledForSite($enabled);
        } else {
            $entry->enabled = $enabled;
            $entry->setEnabledForSite(true);
        }

        // Structure parent
        if ($section->type === Section::TYPE_STRUCTURE && $section->maxLevels !== 1) {
            $entry->setParentId($this->getParentId());
        }

        return $entry;
    }

    /**
     * @inheritdoc
     */
    public function canView(User $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }

        $section = $this->getSection();

        if (!$user->can("viewEntries:$section->uid")) {
            return false;
        }

        if ($this->getIsDraft() && $this->getIsDerivative()) {
            /** @var static|DraftBehavior $this */
            return (
                $this->creatorId === $user->id ||
                $user->can("viewPeerEntryDrafts:$section->uid")
            );
        }

        return (
            $section->type === Section::TYPE_SINGLE ||
            $this->getAuthorId() === $user->id ||
            $user->can("viewPeerEntries:$section->uid")
        );
    }

    /**
     * @inheritdoc
     */
    public function canSave(User $user): bool
    {
        if (parent::canSave($user)) {
            return true;
        }

        $section = $this->getSection();

        if (!$this->id) {
            return $user->can("createEntries:$section->uid");
        }

        if ($this->getIsDraft()) {
            /** @var static|DraftBehavior $this */
            return (
                $this->creatorId === $user->id ||
                $user->can("savePeerEntryDrafts:$section->uid")
            );
        }

        if (!$user->can("saveEntries:$section->uid")) {
            return false;
        }

        return (
            $section->type === Section::TYPE_SINGLE ||
            $this->getAuthorId() === $user->id ||
            $user->can("savePeerEntries:$section->uid")
        );
    }

    /**
     * @inheritdoc
     */
    public function canDuplicate(User $user): bool
    {
        return $this->getSection()->type !== Section::TYPE_SINGLE;
    }

    /**
     * @inheritdoc
     */
    public function canDelete(User $user): bool
    {
        $section = $this->getSection();

        if ($section->type === Section::TYPE_SINGLE && !$this->getIsDraft()) {
            return false;
        }

        if (parent::canDelete($user)) {
            return true;
        }

        if ($this->getIsDraft() && $this->getIsDerivative()) {
            /** @var static|DraftBehavior $this */
            return (
                $this->creatorId === $user->id ||
                $user->can("deletePeerEntries:$section->uid")
            );
        }

        if (!$user->can("deleteEntries:$section->uid")) {
            return false;
        }

        return (
            $this->getAuthorId() === $user->id ||
            $user->can("deletePeerEntries:$section->uid")
        );
    }

    /**
     * @inheritdoc
     */
    public function canDeleteForSite(User $user): bool
    {
        return $this->getSection()->propagationMethod === Section::PROPAGATION_METHOD_CUSTOM;
    }

    /**
     * @inheritdoc
     */
    public function canCreateDrafts(User $user): bool
    {
        // Everyone with view permissions can create drafts
        return true;
    }

    /**
     * @inheritdoc
     */
    public function hasRevisions(): bool
    {
        return $this->getSection()->enableVersioning;
    }

    /**
     * @inheritdoc
     */
    protected function cpEditUrl(): ?string
    {
        $section = $this->getSection();

        $path = sprintf('entries/%s/%s', $section->handle, $this->getCanonicalId());

        // Ignore homepage/temp slugs
        if ($this->slug && strpos($this->slug, '__') !== 0) {
            $path .= "-$this->slug";
        }

        return UrlHelper::cpUrl($path);
    }

    /**
     * @inheritdoc
     */
    public function getPostEditUrl(): ?string
    {
        $section = $this->getSection();
        $sourceKey = $section->type === Section::TYPE_SINGLE ? 'singles' : $section->handle;
        return UrlHelper::cpUrl("entries/$sourceKey");
    }

    /**
     * @inheritdoc
     */
    public function getCrumbs(): array
    {
        $section = $this->getSection();

        $crumbs = [
            [
                'label' => Craft::t('app', 'Entries'),
                'url' => 'entries',
            ],
        ];

        if ($section->type === Section::TYPE_SINGLE) {
            $crumbs[] = [
                'label' => Craft::t('app', 'Singles'),
                'url' => 'entries/singles',
            ];
        } else {
            $crumbs[] = [
                'label' => Craft::t('site', $section->name),
                'url' => "entries/$section->name",
            ];

            if ($section->type === Section::TYPE_STRUCTURE) {
                $user = Craft::$app->getUser()->getIdentity();
                foreach ($this->getCanonical()->getAncestors()->all() as $ancestor) {
                    if ($ancestor->canView($user)) {
                        $crumbs[] = [
                            'label' => $ancestor->title,
                            'url' => $ancestor->getCpEditUrl(),
                        ];
                    }
                }
            }
        }

        return $crumbs;
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
    public function setEagerLoadedElements(string $handle, array $elements): void
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
    public function metaFieldsHtml(bool $static): string
    {
        $fields = [];
        $section = $this->getSection();
        $user = Craft::$app->getUser()->getIdentity();

        if ($section->type !== Section::TYPE_SINGLE) {
            // Type
            $fields[] = (function() use ($static) {
                $entryTypes = $this->getAvailableEntryTypes();
                if (count($entryTypes) <= 1) {
                    return null;
                }

                $entryTypeOptions = [];
                $fieldLayoutIds = [];

                foreach ($entryTypes as $entryType) {
                    $entryTypeOptions[] = [
                        'label' => Craft::t('site', $entryType->name),
                        'value' => $entryType->id,
                    ];
                    $fieldLayoutIds["type-$entryType->id"] = $entryType->fieldLayoutId;
                }

                if (!$static) {
                    $view = Craft::$app->getView();
                    $typeInputId = $view->namespaceInputId('entryType');
                    $js = <<<EOD
(() => {
    const \$typeInput = $('#$typeInputId');
    const editor = \$typeInput.closest('form').data('elementEditor');
    if (editor) {
        editor.checkForm();
    }
})();
EOD;
                    $view->registerJs($js);
                }

                return Cp::selectFieldHtml([
                    'label' => Craft::t('app', 'Entry Type'),
                    'id' => 'entryType',
                    'name' => 'typeId',
                    'value' => $this->getTypeId(),
                    'options' => $entryTypeOptions,
                    'disabled' => $static,
                ]);
            })();
        }

        // Slug
        $fields[] = $this->slugFieldHtml($static);

        // Parent
        if ($section->type === Section::TYPE_STRUCTURE && $section->maxLevels !== 1) {
            $fields[] = (function() use ($static, $section) {
                if ($parentId = $this->getParentId()) {
                    $parent = Craft::$app->getEntries()->getEntryById($parentId, $this->siteId, [
                        'drafts' => null,
                        'draftOf' => false,
                    ]);
                } else {
                    // If the entry already has structure data, use it. Otherwise, use its canonical entry
                    $parent = static::find()
                        ->siteId($this->siteId)
                        ->ancestorOf($this->lft ? $this : ($this->getIsCanonical() ? $this->id : $this->getCanonical(true)))
                        ->ancestorDist(1)
                        ->drafts(null)
                        ->draftOf(false)
                        ->status(null)
                        ->one();
                }

                return Cp::elementSelectFieldHtml([
                    'label' => Craft::t('app', 'Parent'),
                    'id' => 'parentId',
                    'name' => 'parentId',
                    'elementType' => self::class,
                    'selectionLabel' => Craft::t('app', 'Choose'),
                    'sources' => ["section:$section->uid"],
                    'criteria' => $this->_parentOptionCriteria($section),
                    'limit' => 1,
                    'elements' => $parent ? [$parent] : [],
                    'disabled' => $static,
                ]);
            })();
        }

        if ($section->type !== Section::TYPE_SINGLE) {
            // Author
            if (Craft::$app->getEdition() === Craft::Pro && $user->can("viewPeerEntries:$section->uid")) {
                $fields[] = (function() use ($static, $section) {
                    $author = $this->getAuthor();
                    return Cp::elementSelectFieldHtml([
                        'label' => Craft::t('app', 'Author'),
                        'id' => 'authorId',
                        'name' => 'authorId',
                        'elementType' => User::class,
                        'selectionLabel' => Craft::t('app', 'Choose'),
                        'criteria' => [
                            'can' => "viewEntries:$section->uid",
                        ],
                        'single' => true,
                        'elements' => $author ? [$author] : null,
                        'disabled' => $static,
                    ]);
                })();
            }

            // Post Date
            $fields[] = Cp::dateTimeFieldHtml([
                'label' => Craft::t('app', 'Post Date'),
                'id' => 'postDate',
                'name' => 'postDate',
                'value' => $this->postDate,
                'errors' => $this->getErrors('postDate'),
                'disabled' => $static,
            ]);

            // Expiry Date
            $fields[] = Cp::dateTimeFieldHtml([
                'label' => Craft::t('app', 'Expiry Date'),
                'id' => 'expiryDate',
                'name' => 'expiryDate',
                'value' => $this->expiryDate,
                'errors' => $this->getErrors('expiryDate'),
                'disabled' => $static,
            ]);
        }

        $fields[] = parent::metaFieldsHtml($static);

        return implode('', $fields);
    }

    private function _parentOptionCriteria(Section $section): array
    {
        $parentOptionCriteria = [
            'siteId' => $this->siteId,
            'sectionId' => $section->id,
            'status' => null,
            'drafts' => null,
            'draftOf' => false,
        ];

        // Prevent the current entry, or any of its descendants, from being selected as a parent
        if ($this->id) {
            $excludeIds = self::find()
                ->descendantOf($this)
                ->drafts(null)
                ->draftOf(false)
                ->status(null)
                ->ids();
            $excludeIds[] = $this->getCanonicalId();
            $parentOptionCriteria['id'] = array_merge(['not'], $excludeIds);
        }

        if ($section->maxLevels) {
            if ($this->id) {
                // Figure out how deep the ancestors go
                $maxDepth = self::find()
                    ->select('level')
                    ->descendantOf($this)
                    ->status(null)
                    ->leaves()
                    ->scalar();
                $depth = 1 + ($maxDepth ?: $this->level) - $this->level;
            } else {
                $depth = 1;
            }

            $parentOptionCriteria['level'] = sprintf('<=%s', $section->maxLevels - $depth);
        }

        return $parentOptionCriteria;
    }

    /**
     * Updates the entry's title, if its entry type has a dynamic title format.
     *
     * @since 3.0.3
     */
    public function updateTitle(): void
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
    public function beforeValidate(): bool
    {
        if (!$this->getAuthorId() && $this->getSection()->type !== Section::TYPE_SINGLE) {
            $this->setAuthorId(Craft::$app->getUser()->getId());
        }

        if ($this->scenario === self::SCENARIO_LIVE && !$this->postDate) {
            // Default the post date to the current date/time
            $this->postDate = new DateTime();
            // ...without the seconds
            $this->postDate->setTimestamp($this->postDate->getTimestamp() - ($this->postDate->getTimestamp() % 60));
            // ...unless an expiry date is set in the past
            if ($this->expiryDate && $this->postDate >= $this->expiryDate) {
                $this->postDate = (clone $this->expiryDate)->modify('-1 day');
            }
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
            throw new UnsupportedSiteException($this, $this->siteId, "The section '$section->name' is not enabled for the site '$this->siteId'");
        }

        // Make sure the entry has at least one revision if the section has versioning enabled
        if ($this->_shouldSaveRevision()) {
            $hasRevisions = self::find()
                ->revisionOf($this)
                ->site('*')
                ->status(null)
                ->exists();
            if (!$hasRevisions) {
                $currentEntry = self::find()
                    ->id($this->id)
                    ->site('*')
                    ->status(null)
                    ->one();

                // May be null if the entry is currently stored as an unpublished draft
                if ($currentEntry) {
                    $revisionNotes = 'Revision from ' . Craft::$app->getFormatter()->asDatetime($currentEntry->dateUpdated);
                    Craft::$app->getRevisions()->createRevision($currentEntry, $currentEntry->getAuthorId(), $revisionNotes);
                }
            }
        }

        // Set the structure ID for Element::attributes() and afterSave()
        if ($section->type === Section::TYPE_STRUCTURE) {
            $this->structureId = $section->structureId;

            // Has the entry been assigned to a new parent?
            if (!$this->duplicateOf && $this->hasNewParent()) {
                if ($parentId = $this->getParentId()) {
                    $parentEntry = Craft::$app->getEntries()->getEntryById($parentId, '*', [
                        'preferSites' => [$this->siteId],
                        'drafts' => null,
                        'draftOf' => false,
                    ]);

                    if (!$parentEntry) {
                        throw new InvalidConfigException("Invalid parent ID: $parentId");
                    }
                } else {
                    $parentEntry = null;
                }

                $this->setParent($parentEntry);
            }
        }

        // Section type-specific stuff
        if ($section->type == Section::TYPE_SINGLE) {
            $this->setAuthorId(null);
            $this->expiryDate = null;
        }

        $this->updateTitle();

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     * @throws Exception if reasons
     */
    public function afterSave(bool $isNew): void
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
            $record->typeId = $this->getTypeId();
            $record->authorId = $this->getAuthorId();
            $record->postDate = $this->postDate;
            $record->expiryDate = $this->expiryDate;

            // Capture the dirty attributes from the record
            $dirtyAttributes = array_keys($record->getDirtyAttributes());

            $record->save(false);

            if ($this->getIsCanonical() && $section->type == Section::TYPE_STRUCTURE) {
                // Has the parent changed?
                if ($this->hasNewParent()) {
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
        $parentId = $this->getParentId();
        $structuresService = Craft::$app->getStructures();

        // If this is a provisional draft and its new parent matches the canonical entry’s, just drop it from the structure
        if ($this->isProvisionalDraft) {
            $canonicalParentId = self::find()
                ->select(['elements.id'])
                ->ancestorOf($this->getCanonicalId())
                ->ancestorDist(1)
                ->status(null)
                ->scalar();

            if ($parentId == $canonicalParentId) {
                $structuresService->remove($this->structureId, $this);
                return;
            }
        }

        $mode = $isNew ? Structures::MODE_INSERT : Structures::MODE_AUTO;

        if (!$parentId) {
            if ($section->defaultPlacement === Section::DEFAULT_PLACEMENT_BEGINNING) {
                $structuresService->prependToRoot($this->structureId, $this, $mode);
            } else {
                $structuresService->appendToRoot($this->structureId, $this, $mode);
            }
        } else {
            if ($section->defaultPlacement === Section::DEFAULT_PLACEMENT_BEGINNING) {
                $structuresService->prepend($this->structureId, $this, $this->getParent(), $mode);
            } else {
                $structuresService->append($this->structureId, $this, $this->getParent(), $mode);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function afterPropagate(bool $isNew): void
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
                ->status(null)
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
    public function afterRestore(): void
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
    public function afterMoveInStructure(int $structureId): void
    {
        // Was the entry moved within its section's structure?
        $section = $this->getSection();

        if ($section->type == Section::TYPE_STRUCTURE && $section->structureId == $structureId) {
            Craft::$app->getElements()->updateElementSlugAndUri($this, true, true, true);

            // If this is the canonical entry, update its drafts
            if ($this->getIsCanonical()) {
                $drafts = static::find()
                    ->draftOf($this)
                    ->status(null)
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
        return $context->getSection()->handle . '_' . $context->handle;
    }
}
