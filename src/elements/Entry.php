<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements;

use Craft;
use craft\base\Colorable;
use craft\base\Element;
use craft\base\ExpirableElementInterface;
use craft\base\Field;
use craft\base\Iconic;
use craft\base\NestedElementInterface;
use craft\base\NestedElementTrait;
use craft\behaviors\DraftBehavior;
use craft\controllers\ElementIndexesController;
use craft\db\Connection;
use craft\db\FixedOrderExpression;
use craft\db\Query;
use craft\db\Table;
use craft\elements\actions\Delete;
use craft\elements\actions\DeleteForSite;
use craft\elements\actions\Duplicate;
use craft\elements\actions\NewChild;
use craft\elements\actions\NewSiblingAfter;
use craft\elements\actions\NewSiblingBefore;
use craft\elements\actions\Restore;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\conditions\entries\EntryCondition;
use craft\elements\conditions\entries\SectionConditionRule;
use craft\elements\conditions\entries\TypeConditionRule;
use craft\elements\db\EagerLoadPlan;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\EntryQuery;
use craft\enums\CmsEdition;
use craft\enums\Color;
use craft\enums\PropagationMethod;
use craft\events\DefineEntryTypesEvent;
use craft\events\ElementCriteriaEvent;
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
use craft\services\ElementSources;
use craft\services\Structures;
use craft\validators\ArrayValidator;
use craft\validators\DateCompareValidator;
use craft\validators\DateTimeValidator;
use DateTime;
use Illuminate\Support\Collection;
use Throwable;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\Expression;

/**
 * Entry represents an entry element.
 *
 * @property int $typeId the entry type’s ID
 * @property EntryType $type the entry type
 * @property Section|null $section the entry’s section
 * @property User|null $author the primary entry author
 * @property User[] $authors the entry authors
 * @property int|null $authorId The primary entry author’s ID
 * @property int[] $authorIds the entry authors’ IDs
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Entry extends Element implements NestedElementInterface, ExpirableElementInterface, Iconic, Colorable
{
    use NestedElementTrait {
        eagerLoadingMap as traitEagerLoadingMap;
        attributes as traitAttributes;
        extraFields as traitExtraFields;
        setEagerLoadedElements as traitSetEagerLoadedElements;
    }

    public const STATUS_LIVE = 'live';
    public const STATUS_PENDING = 'pending';
    public const STATUS_EXPIRED = 'expired';

    /**
     * @event DefineEntryTypesEvent The event that is triggered when defining the available entry types for the entry
     * @see getAvailableEntryTypes()
     * @since 3.6.0
     */
    public const EVENT_DEFINE_ENTRY_TYPES = 'defineEntryTypes';

    /**
     * @event ElementCriteriaEvent The event that is triggered when defining the parent selection criteria.
     * @see _parentOptionCriteria()
     * @since 4.4.0
     */
    public const EVENT_DEFINE_PARENT_SELECTION_CRITERIA = 'defineParentSelectionCriteria';

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
    public static function hasDrafts(): bool
    {
        return true;
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
    public static function find(): EntryQuery
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
        if ($context === ElementSources::CONTEXT_INDEX) {
            $sections = Craft::$app->getEntries()->getEditableSections();
            $editable = true;
        } else {
            $sections = Craft::$app->getEntries()->getAllSections();
            $editable = null;
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
                        $source['structureEditable'] = $user && $user->can("saveEntries:$section->uid");
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
     */
    public static function modifyCustomSource(array $config): array
    {
        if (empty($config['condition']['conditionRules'])) {
            return $config;
        }

        // see if it's limited to one section
        /** @var SectionConditionRule|null $sectionRule */
        $sectionRule = ArrayHelper::firstWhere(
            $config['condition']['conditionRules'],
            fn(array $rule) => $rule['class'] === SectionConditionRule::class,
        );
        $sectionOptions = $sectionRule['values'] ?? null;

        if ($sectionOptions && count($sectionOptions) === 1) {
            $section = Craft::$app->getEntries()->getSectionByUid(reset($sectionOptions));
            if ($section) {
                $config['data']['handle'] = $section->handle;
            }
        }

        // see if it specifies any entry types
        /** @var TypeConditionRule|null $entryTypeRule */
        $entryTypeRule = ArrayHelper::firstWhere(
            $config['condition']['conditionRules'],
            fn(array $rule) => $rule['class'] === TypeConditionRule::class,
        );
        $entryTypeOptions = $entryTypeRule['values'] ?? null;

        if ($entryTypeOptions) {
            $entryType = Craft::$app->getEntries()->getEntryTypeByUid(reset($entryTypeOptions));
            if ($entryType) {
                $config['data']['entry-type'] = $entryType->handle;
            }
        }

        return $config;
    }

    /**
     * @inheritdoc
     */
    protected static function defineFieldLayouts(?string $source): array
    {
        if ($source !== null) {
            if ($source === '*') {
                $sections = Craft::$app->getEntries()->getAllSections();
            } elseif ($source === 'singles') {
                $sections = Craft::$app->getEntries()->getSectionsByType(Section::TYPE_SINGLE);
            } else {
                $sections = [];
                if (preg_match('/^section:(.+)$/', $source, $matches)) {
                    $section = Craft::$app->getEntries()->getSectionByUid($matches[1]);
                    if ($section) {
                        $sections[] = $section;
                    }
                }
            }

            $entryTypes = array_values(array_unique(array_merge(
                ...array_map(fn(Section $section) => $section->getEntryTypes(), $sections),
            )));
        } else {
            // get all entry types, including those which may only be used by Matrix fields
            $entryTypes = Craft::$app->getEntries()->getAllEntryTypes();
        }

        return array_map(fn(EntryType $entryType) => $entryType->getFieldLayout(), $entryTypes);
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

        // Get the section we need to check permissions on
        if (preg_match('/^section:(\d+)$/', $source, $matches)) {
            $section = Craft::$app->getEntries()->getSectionById((int)$matches[1]);
        } elseif (preg_match('/^section:(.+)$/', $source, $matches)) {
            $section = Craft::$app->getEntries()->getSectionByUid($matches[1]);
        } else {
            $section = null;
        }

        // Now figure out what we can do with these
        $actions = [];
        $elementsService = Craft::$app->getElements();

        if ($section) {
            $user = Craft::$app->getUser()->getIdentity();

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
                    'newSiblingUrl' => $newEntryUrl,
                ]);

                $actions[] = $elementsService->createAction([
                    'type' => NewSiblingAfter::class,
                    'newSiblingUrl' => $newEntryUrl,
                ]);

                if ($section->maxLevels != 1) {
                    $actions[] = $elementsService->createAction([
                        'type' => NewChild::class,
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

                if ($section->propagationMethod === PropagationMethod::Custom && $section->getHasMultiSiteEntries()) {
                    $actions[] = DeleteForSite::class;
                }
            }
        }

        // Restore
        $actions[] = Restore::class;

        return $actions;
    }

    /**
     * @inheritdoc
     */
    protected static function includeSetStatusAction(): bool
    {
        return true;
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
                'label' => Craft::t('app', 'Section'),
                'orderBy' => function(int $dir, Connection $db) {
                    $sectionIds = Collection::make(Craft::$app->getEntries()->getAllSections())
                        ->sort(fn(Section $a, Section $b) => $dir === SORT_ASC
                            ? $a->name <=> $b->name
                            : $b->name <=> $a->name)
                        ->map(fn(Section $section) => $section->id)
                        ->all();
                    return new FixedOrderExpression('entries.sectionId', $sectionIds, $db);
                },
                'attribute' => 'section',
            ],
            [
                'label' => Craft::t('app', 'Entry Type'),
                'orderBy' => function(int $dir, Connection $db) {
                    $entryTypeIds = Collection::make(Craft::$app->getEntries()->getAllEntryTypes())
                        ->sort(fn(EntryType $a, EntryType $b) => $dir === SORT_ASC
                            ? $a->name <=> $b->name
                            : $b->name <=> $a->name)
                        ->map(fn(EntryType $type) => $type->id)
                        ->all();
                    return new FixedOrderExpression('entries.typeId', $entryTypeIds, $db);
                },
                'attribute' => 'type',
            ],
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
                'orderBy' => 'dateCreated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'dateUpdated',
                'defaultDir' => 'desc',
            ],
            'id' => Craft::t('app', 'ID'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        $attributes = array_merge(parent::defineTableAttributes(), [
            'section' => ['label' => Craft::t('app', 'Section')],
            'type' => ['label' => Craft::t('app', 'Entry Type')],
            'authors' => ['label' => Craft::t('app', 'Authors')],
            'ancestors' => ['label' => Craft::t('app', 'Ancestors')],
            'parent' => ['label' => Craft::t('app', 'Parent')],
            'postDate' => ['label' => Craft::t('app', 'Post Date')],
            'expiryDate' => ['label' => Craft::t('app', 'Expiry Date')],
            'revisionNotes' => ['label' => Craft::t('app', 'Revision Notes')],
            'revisionCreator' => ['label' => Craft::t('app', 'Last Edited By')],
            'drafts' => ['label' => Craft::t('app', 'Drafts')],
        ]);

        // Hide Author & Last Edited By from Craft Solo
        if (Craft::$app->edition === CmsEdition::Solo) {
            unset($attributes['authors'], $attributes['revisionCreator']);
        }

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = ['status'];

        if ($source === '*') {
            $attributes[] = 'section';
        }

        if ($source !== 'singles') {
            $attributes[] = 'postDate';
            $attributes[] = 'expiryDate';
            $attributes[] = 'authors';
        }

        $attributes[] = 'link';

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle): array|null|false
    {
        switch ($handle) {
            case 'author':
            case 'authors':
                $map = [];

                /** @var self[] $sourceElements */
                foreach ($sourceElements as $entry) {
                    foreach ($entry->getAuthorIds() as $authorId) {
                        $map[] = [
                            'source' => $entry->id,
                            'target' => $authorId,
                        ];
                    }
                }

                return [
                    'elementType' => User::class,
                    'map' => $map,
                    'criteria' => [
                        'status' => null,
                    ],
                ];

            default:
                return self::traitEagerLoadingMap($sourceElements, $handle);
        }
    }

    /**
     * Returns the GraphQL type name that entries should use, based on their entry type.
     *
     * @since 5.0.0
     */
    public static function gqlTypeName(EntryType $entryType): string
    {
        return sprintf('%s_Entry', $entryType->handle);
    }

    /**
     * @inheritdoc
     */
    public static function gqlScopesByContext(mixed $context): array
    {
        /** @var Section $section */
        $section = $context['section'];
        return [
            "sections.$section->uid",
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function prepElementQueryForTableAttribute(ElementQueryInterface $elementQuery, string $attribute): void
    {
        switch ($attribute) {
            case 'authors':
                $elementQuery->andWith(['authors', ['status' => null]]);
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
     * @var bool Collapsed
     * @since 5.0.0
     */
    public bool $collapsed = false;

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
     * @var int[] Entry author IDs
     * @see getAuthorIds()
     * @see setAuthorIds()
     */
    private array $_authorIds;

    /**
     * @var int[] Original entry author IDs
     * @see setAuthorIds()
     */
    private array $_oldAuthorIds;

    /**
     * @var User[]|null Entry authors
     * @see getAuthors()
     * @see setAuthors()
     */
    private ?array $_authors = null;

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
        $names = array_flip($this->traitAttributes());
        unset($names['deletedWithEntryType']);
        $names['authorId'] = true;
        $names['authorIds'] = true;
        $names['typeId'] = true;
        return array_keys($names);
    }

    /**
     * @inheritdoc
     */
    public function extraFields(): array
    {
        $names = $this->traitExtraFields();
        $names[] = 'author';
        $names[] = 'authors';
        $names[] = 'section';
        $names[] = 'type';
        return $names;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return array_merge(parent::attributeLabels(), [
            'authorIds' => Craft::t('app', '{max, plural, =1{Author} other {Authors}}', [
                'max' => $this->getSection()?->maxAuthors ?? 1,
            ]),
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
        $rules[] = [['sectionId', 'fieldId', 'ownerId', 'primaryOwnerId', 'typeId', 'sortOrder'], 'number', 'integerOnly' => true];
        $rules[] = [['authorIds'], 'each', 'rule' => ['number', 'integerOnly' => true]];
        $rules[] = [
            ['sectionId'],
            'required',
            'when' => fn() => !isset($this->fieldId),
        ];
        $rules[] = [
            ['typeId'],
            function(string $attribute) {
                if (!$this->isEntryTypeAllowed()) {
                    $this->addError($attribute, Craft::t('app', '{type} entries are no longer allowed in this section. Please choose a different entry type.', [
                        'type' => $this->getType()->getUiLabel(),
                    ]));
                }
            },
            'skipOnEmpty' => false,
            'when' => fn() => $this->getIsCanonical(),
        ];
        $rules[] = [['fieldId'], function(string $attribute) {
            if (isset($this->sectionId)) {
                $this->addError($attribute, Craft::t('app', '`sectionId` and `fieldId` cannot both be set on an entry.'));
            }
        }];
        $rules[] = [['postDate', 'expiryDate'], DateTimeValidator::class];
        $rules[] = [['postDate', 'expiryDate'], DateTimeValidator::class];
        $rules[] = [
            ['postDate'],
            DateCompareValidator::class,
            'operator' => '<',
            'compareAttribute' => 'expiryDate',
            'when' => fn() => $this->postDate && $this->expiryDate,
            'on' => self::SCENARIO_LIVE,
        ];

        $section = $this->getSection();
        if ($section && $section->type !== Section::TYPE_SINGLE) {
            $rules[] = [['authorIds'], 'required', 'on' => self::SCENARIO_LIVE];
            $rules[] = [
                ['authorIds'],
                ArrayValidator::class,
                'max' => $section->maxAuthors,
                'tooMany' => Craft::t('app', '{num, plural, =1{Only one author is} other{Up to {num, number} authors are}} allowed.', [
                    'num' => $section->maxAuthors,
                ]),
            ];
        }

        return $rules;
    }

    /**
     * @inheritdoc
     */
    protected function shouldValidateTitle(): bool
    {
        return $this->getType()->hasTitleField;
    }

    /**
     * @inheritdoc
     */
    public function getColor(): ?Color
    {
        return $this->getType()->getColor();
    }

    /**
     * @inheritdoc
     */
    public function getSupportedSites(): array
    {
        if (isset($this->fieldId)) {
            return $this->getField()->getSupportedSitesForElement($this);
        }

        if (!isset($this->sectionId)) {
            throw new InvalidConfigException('Either `sectionId` or `fieldId` + `ownerId` must be set on the entry.');
        }

        $section = $this->getSection();
        /** @var Site[] $allSites */
        $allSites = ArrayHelper::index(Craft::$app->getSites()->getAllSites(true), 'id');
        $sites = [];

        // If the section is leaving it up to entries to decide which sites to be propagated to,
        // figure out which sites the entry is currently saved in
        if (
            ($this->duplicateOf->id ?? $this->id) &&
            $section->propagationMethod === PropagationMethod::Custom
        ) {
            if ($this->id) {
                $currentSites = self::find()
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
                array_push($currentSites, ...self::find()
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
                case PropagationMethod::None:
                    $include = $siteSettings->siteId == $this->siteId;
                    $propagate = true;
                    break;
                case PropagationMethod::SiteGroup:
                    $include = $allSites[$siteSettings->siteId]->groupId == $allSites[$this->siteId]->groupId;
                    $propagate = true;
                    break;
                case PropagationMethod::Language:
                    $include = $allSites[$siteSettings->siteId]->language == $allSites[$this->siteId]->language;
                    $propagate = true;
                    break;
                case PropagationMethod::Custom:
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
    protected function cacheTags(): array
    {
        $tags = [
            sprintf('entryType:%s', $this->getTypeId()),
        ];

        // Did the entry type just change?
        if ($this->getTypeId() !== $this->_oldTypeId) {
            $tags[] = "entryType:$this->_oldTypeId";
        }

        if (isset($this->sectionId)) {
            $tags[] = "section:$this->sectionId";
        } elseif (isset($this->fieldId)) {
            $tags[] = "field:$this->fieldId";
        }

        return $tags;
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException if [[siteId]] is not set to a site ID that the entry’s section is enabled for
     */
    public function getUriFormat(): ?string
    {
        if (isset($this->fieldId)) {
            return $this->getField()->getUriFormatForElement($this);
        }

        if (!isset($this->sectionId)) {
            throw new InvalidConfigException('Either `sectionId` or `fieldId` + `ownerId` must be set on the entry.');
        }

        $sectionSiteSettings = $this->getSection()->getSiteSettings();

        if (!isset($sectionSiteSettings[$this->siteId])) {
            throw new InvalidConfigException('Entry’s section (' . $this->sectionId . ') is not enabled for site ' . $this->siteId);
        }

        return $sectionSiteSettings[$this->siteId]->uriFormat;
    }

    /**
     * @inheritdoc
     */
    protected function route(): array|string|null
    {
        // Make sure that the entry is actually live
        if (!$this->previewing && $this->getStatus() != self::STATUS_LIVE) {
            return null;
        }

        $section = $this->getSection();

        if (!$section) {
            return null;
        }

        // Make sure the section is set to have URLs for this site
        $sectionSiteSettings = $section->getSiteSettings()[$this->siteId] ?? null;

        if (!$sectionSiteSettings?->hasUrls) {
            return null;
        }

        return [
            'templates/render', [
                'template' => (string)$sectionSiteSettings->template,
                'variables' => [
                    'entry' => $this,
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function crumbs(): array
    {
        $section = $this->getSection();

        if (!$section) {
            return [];
        }

        $sections = Collection::make(Craft::$app->getEntries()->getEditableSections());
        /** @var Collection $sectionOptions */
        $sectionOptions = $sections
            ->filter(fn(Section $s) => $s->type !== Section::TYPE_SINGLE)
            ->map(fn(Section $s) => [
                'label' => Craft::t('site', $s->name),
                'url' => "entries/$s->handle",
                'selected' => $s->id === $section->id,
            ]);

        if ($sections->contains(fn(Section $s) => $s->type === Section::TYPE_SINGLE)) {
            $sectionOptions->prepend([
                'label' => Craft::t('app', 'Singles'),
                'url' => 'entries/singles',
                'selected' => $section->type === Section::TYPE_SINGLE,
            ]);
        }

        $crumbs = [
            [
                'label' => Craft::t('app', 'Entries'),
                'url' => 'entries',
            ],
            [
                'menu' => [
                    'label' => Craft::t('app', 'Select section'),
                    'items' => $sectionOptions->all(),
                ],
            ],
        ];

        if ($section->type === Section::TYPE_STRUCTURE) {
            $elementsService = Craft::$app->getElements();
            $user = Craft::$app->getUser()->getIdentity();

            foreach ($this->getAncestors()->all() as $ancestor) {
                if ($elementsService->canView($ancestor, $user)) {
                    $crumbs[] = ['html' => Cp::elementChipHtml($ancestor)];
                }
            }
        }

        return $crumbs;
    }

    /**
     * @inheritdoc
     */
    public function getUiLabel(): string
    {
        if ($this->fieldId) {
            $entryType = $this->getType();
            if (!$entryType->hasTitleField && !$entryType->titleFormat) {
                return '';
            }
        }

        return parent::getUiLabel();
    }

    /**
     * @inheritdoc
     */
    protected function uiLabel(): ?string
    {
        if (!$this->fieldId && (!isset($this->title) || trim($this->title) === '')) {
            return Craft::t('app', 'Untitled {type}', [
                'type' => self::lowerDisplayName(),
            ]);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getChipLabelHtml(): string
    {
        $html = parent::getChipLabelHtml();
        if ($html !== '') {
            return $html;
        }

        return Html::tag('em', Craft::t('site', $this->getType()->name), [
            'class' => 'light',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getCardBodyHtml(): ?string
    {
        $html = parent::getCardBodyHtml();
        if ($html === '') {
            return Html::tag('div', Html::tag('em', Craft::t('site', $this->getType()->name)));
        }
        return $html;
    }

    /**
     * @inheritdoc
     */
    protected function previewTargets(): array
    {
        if ($this->fieldId) {
            return parent::previewTargets();
        }

        return array_map(function($previewTarget) {
            $previewTarget['label'] = Craft::t('site', $previewTarget['label']);
            return $previewTarget;
        }, $this->getSection()?->previewTargets ?? []);
    }

    /**
     * @inheritdoc
     */
    public function getIcon(): ?string
    {
        return $this->getType()->getIcon();
    }

    /**
     * Returns the reference string to this element.
     *
     * @return string|null
     */
    public function getRef(): ?string
    {
        if (isset($this->fieldId)) {
            return null;
        }

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
    public function getIsSlugTranslatable(): bool
    {
        return ($this->getType()->slugTranslationMethod !== Field::TRANSLATION_METHOD_NONE);
    }

    /**
     * @inheritdoc
     */
    public function getSlugTranslationDescription(): ?string
    {
        return ElementHelper::translationDescription($this->getType()->slugTranslationMethod);
    }

    /**
     * @inheritdoc
     */
    public function getSlugTranslationKey(): string
    {
        $type = $this->getType();
        return ElementHelper::translationKey($this, $type->slugTranslationMethod, $type->slugTranslationKeyFormat);
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
        } catch (InvalidConfigException) {
            // The entry type was probably deleted
            return null;
        }
        return $entryType->getFieldLayout();
    }

    /**
     * @inheritdoc
     */
    public function getExpiryDate(): ?DateTime
    {
        return $this->expiryDate;
    }

    /**
     * Returns the entry’s section.
     *
     * ---
     * ```php
     * $section = $entry->section;
     * ```
     * ```twig
     * {% set section = entry.section %}
     * ```
     *
     * @return Section|null
     * @throws InvalidConfigException if [[sectionId]] is missing or invalid
     */
    public function getSection(): ?Section
    {
        if (!isset($this->sectionId)) {
            return null;
        }

        $section = Craft::$app->getEntries()->getSectionById($this->sectionId);
        if (!$section) {
            throw new InvalidConfigException("Invalid section ID: $this->sectionId");
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
        if (isset($this->fieldId)) {
            /** @var EntryType[] $entryTypes */
            $entryTypes = $this->getField()->getFieldLayoutProviders();
        } else {
            $entryTypes = $this->getSection()->getEntryTypes();
        }

        // Fire a 'defineEntryTypes' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_ENTRY_TYPES)) {
            $event = new DefineEntryTypesEvent(['entryTypes' => $entryTypes]);
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
     * @throws InvalidConfigException if [[typeId]] is invalid, or the section has no entry types
     */
    public function getType(): EntryType
    {
        if (!isset($this->_typeId)) {
            // Default to the section/field's first entry type
            $entryTypes = $this->getAvailableEntryTypes();
            if (!$entryTypes) {
                throw new InvalidConfigException('Entry is missing its type ID');
            }
            $this->_typeId = $entryTypes[0]->id;
        }

        $entryType = Craft::$app->getEntries()->getEntryTypeById($this->_typeId);
        if (!$entryType) {
            throw new InvalidConfigException("Invalid entry type ID: $this->_typeId");
        }
        return $entryType;
    }

    /**
     * Returns the entry author’s ID.
     *
     * @return int|null
     * @since 4.0.0
     */
    public function getAuthorId(): ?int
    {
        return $this->getAuthorIds()[0] ?? null;
    }

    /**
     * Sets the entry author’s ID.
     *
     * @param int|array{0:int}|string|null $authorId
     * @since 4.0.0
     */
    public function setAuthorId(array|int|string|null $authorId): void
    {
        $authorId = $this->normalizeAuthorIds($authorId)[0] ?? null;
        $this->setAuthorIds($authorId);
    }

    /**
     * Returns the primary entry authors’ IDs.
     *
     * @return int[]
     * @since 5.0.0
     */
    public function getAuthorIds(): array
    {
        if (!isset($this->_authorIds)) {
            $this->_authorIds = array_map(fn(User $author) => $author->id, $this->getAuthors());
        }

        return $this->_authorIds;
    }

    /**
     * Sets the entry authors’ IDs.
     *
     * @param User[]|int[]|string|int|null $authorIds
     * @since 5.0.0
     */
    public function setAuthorIds(array|string|int|null $authorIds): void
    {
        $authorIds = $this->normalizeAuthorIds($authorIds);

        if (isset($this->_authorIds)) {
            if ($authorIds === $this->_authorIds) {
                return;
            }

            if (!isset($this->_oldAuthorIds)) {
                // remember the old IDs so we know if this has been modified
                $this->_oldAuthorIds = $this->_authorIds;
            }
        }

        $this->_authorIds = $authorIds;
        $this->_authors = null;
    }

    private function normalizeAuthorIds(array|string|int|null $authorIds): array
    {
        if ($authorIds === '' || $authorIds === null) {
            return [];
        }

        // make sure we're working with an array
        if (!is_array($authorIds)) {
            $authorIds = ArrayHelper::toArray($authorIds);
        }

        return array_map(fn($id) => (int)$id, $authorIds);
    }

    /**
     * Returns the entry author.
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
        return $this->getAuthors()[0] ?? null;
    }

    /**
     * Sets the entry author.
     *
     * @param User|null $author
     */
    public function setAuthor(?User $author = null): void
    {
        $this->setAuthors($author ? [$author] : []);
    }

    /**
     * Returns the entry authors.
     *
     * ---
     * ```php
     * $authors = $entry->authors;
     * ```
     * ```twig
     * {% for author in entry.authors %}
     *     <p>By {{ author.name }}</p>
     * {% endfor %}
     * ```
     *
     * @return User[]
     * @since 5.0.0
     */
    public function getAuthors(): array
    {
        if (!isset($this->_authors)) {
            if (isset($this->_authorIds)) {
                $authors = User::find()
                    ->id($this->_authorIds)
                    ->fixedOrder()
                    ->status(null)
                    ->all();
            } else {
                $authors = User::find()
                    ->authorOf($this)
                    ->status(null)
                    ->innerJoin(['entries_authors' => Table::ENTRIES_AUTHORS], [
                        'and',
                        ['entries_authors.entryId' => $this->id],
                        '[[entries_authors.authorId]] = [[users.id]]',
                    ])
                    ->orderBy(['entries_authors.sortOrder' => SORT_ASC])
                    ->all();
            }

            $this->setAuthors($authors);
        }

        return $this->_authors;
    }

    /**
     * Sets the entry authors.
     *
     * @param User[] $authors
     * @since 5.0.0
     */
    public function setAuthors(array $authors): void
    {
        $this->_authors = $authors;
        $this->_authorIds = array_map(fn(User $author) => $author->id, $authors);
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
            $expiryDate = $this->expiryDate?->getTimestamp();

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
    public function createAnother(): ?self
    {
        /** @var self $entry */
        $entry = Craft::createObject([
            'class' => self::class,
            'sectionId' => $this->sectionId,
            'fieldId' => $this->fieldId,
            'primaryOwnerId' => $this->getPrimaryOwnerId(),
            'ownerId' => $this->getPrimaryOwnerId(),
            'sortOrder' => null,
            'typeId' => $this->typeId,
            'siteId' => $this->siteId,
            'authorIds' => $this->getAuthorIds(),
        ]);

        $section = $this->getSection();
        if ($section) {
            // Set the default status based on the section's settings
            /** @var Section_SiteSettings $siteSettings */
            $siteSettings = ArrayHelper::firstWhere($section->getSiteSettings(), 'siteId', $this->siteId);
            $enabled = $siteSettings->enabledByDefault;
        } else {
            $enabled = true;
        }

        if (Craft::$app->getIsMultiSite() && count($entry->getSupportedSites()) > 1) {
            $entry->enabled = true;
            $entry->setEnabledForSite($enabled);
        } else {
            $entry->enabled = $enabled;
            $entry->setEnabledForSite(true);
        }

        // Structure parent
        if ($section?->type === Section::TYPE_STRUCTURE && $section->maxLevels !== 1) {
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

        if (!$section) {
            return false;
        }

        if (!$user->can("viewEntries:$section->uid")) {
            return false;
        }

        if ($this->getIsDraft()) {
            /** @var static|DraftBehavior $this */
            return (
                $this->creatorId === $user->id ||
                $user->can("viewPeerEntryDrafts:$section->uid")
            );
        }

        return (
            $section->type === Section::TYPE_SINGLE ||
            in_array($user->id, $this->getAuthorIds(), true) ||
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

        if (!$section) {
            return false;
        }

        if (!$this->id) {
            return (
                $section->type !== Section::TYPE_SINGLE &&
                $user->can("createEntries:$section->uid")
            );
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
            in_array($user->id, $this->getAuthorIds(), true) ||
            $user->can("savePeerEntries:$section->uid")
        );
    }

    /**
     * @inheritdoc
     */
    public function canDuplicate(User $user): bool
    {
        if (parent::canDuplicate($user)) {
            return true;
        }

        $section = $this->getSection();

        if (!$section) {
            return false;
        }

        return (
            $section->type !== Section::TYPE_SINGLE &&
            $user->can("createEntries:$section->uid") &&
            $user->can("saveEntries:$section->uid")
        );
    }

    /**
     * @inheritdoc
     */
    public function canDuplicateAsDraft(User $user): bool
    {
        if (parent::canDuplicate($user)) {
            return true;
        }

        $section = $this->getSection();

        if (!$section) {
            return false;
        }

        return (
            $section->type !== Section::TYPE_SINGLE &&
            $user->can("createEntries:$section->uid")
        );
    }

    /**
     * @inheritdoc
     */
    public function canDelete(User $user): bool
    {
        if (parent::canDelete($user)) {
            return true;
        }

        $section = $this->getSection();

        if (!$section) {
            return false;
        }

        if ($section->type === Section::TYPE_SINGLE && !$this->getIsDraft()) {
            return false;
        }

        if ($this->getIsDraft()) {
            /** @var static|DraftBehavior $this */
            return (
                $this->creatorId === $user->id ||
                $user->can("deletePeerEntryDrafts:$section->uid")
            );
        }

        if (!$user->can("deleteEntries:$section->uid")) {
            return false;
        }

        return (
            in_array($user->id, $this->getAuthorIds(), true) ||
            $user->can("deletePeerEntries:$section->uid")
        );
    }

    /**
     * @inheritdoc
     */
    public function canDeleteForSite(User $user): bool
    {
        if (parent::canDeleteForSite($user)) {
            return true;
        }

        $section = $this->getSection();

        if (!$section) {
            return false;
        }

        return $section->propagationMethod === PropagationMethod::Custom;
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
        return $this->getSection()?->enableVersioning ?? false;
    }

    /**
     * @inheritdoc
     */
    protected function cpEditUrl(): ?string
    {
        $section = $this->getSection();

        if (!$section) {
            // use the generic element editor URL
            return ElementHelper::elementEditorUrl($this, false);
        }

        $path = sprintf('entries/%s/%s', $section->handle, $this->getCanonicalId());

        // Ignore homepage/temp slugs
        if ($this->slug && !str_starts_with($this->slug, '__')) {
            $path .= sprintf('-%s', str_replace('/', '-', $this->slug));
        }

        return $path;
    }

    /**
     * @inheritdoc
     */
    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl('entries');
    }

    /**
     * @inheritdoc
     */
    protected function cpRevisionsUrl(): ?string
    {
        return sprintf('%s/revisions', $this->cpEditUrl());
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getGqlTypeName(): string
    {
        return self::gqlTypeName($this->getType());
    }

    /**
     * @inheritdoc
     */
    public function setEagerLoadedElements(string $handle, array $elements, EagerLoadPlan $plan): void
    {
        switch ($plan->handle) {
            case 'author':
            case 'authors':
                /** @var User[] $elements */
                $this->setAuthors($elements);
                break;
            default:
                $this->traitSetEagerLoadedElements($handle, $elements, $plan);
        }
    }

    // Indexes, etc.
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    protected function attributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'authors':
                $authors = $this->getAuthors();
                $html = '';
                if (!empty($authors)) {
                    foreach ($authors as $author) {
                        $html .= Cp::elementChipHtml($author);
                    }
                }
                return $html;
            case 'section':
                $section = $this->getSection();
                return $section ? Html::encode(Craft::t('site', $section->name)) : '';
            case 'type':
                try {
                    return Html::encode(Craft::t('site', $this->getType()->name));
                } catch (InvalidConfigException) {
                    return Craft::t('app', 'Unknown');
                }
            default:
                return parent::attributeHtml($attribute);
        }
    }

    /**
     * @inheritdoc
     */
    public function metaFieldsHtml(bool $static): string
    {
        $fields = [];
        $view = Craft::$app->getView();
        $section = $this->getSection();
        $user = Craft::$app->getUser()->getIdentity();

        if ($section?->type !== Section::TYPE_SINGLE) {
            // Type
            $fields[] = (function() use ($static) {
                $entryTypes = $this->getAvailableEntryTypes();
                if (!ArrayHelper::contains($entryTypes, fn(EntryType $entryType) => $entryType->id === $this->typeId)) {
                    $entryTypes[] = $this->getType();
                }
                if (count($entryTypes) <= 1 && $this->isEntryTypeAllowed($entryTypes)) {
                    return null;
                }

                return Cp::customSelectFieldHtml([
                    'status' => $this->getAttributeStatus('typeId'),
                    'label' => Craft::t('app', 'Entry Type'),
                    'id' => 'entryType',
                    'name' => 'typeId',
                    'value' => $this->getType()->id,
                    'options' => array_map(fn(EntryType $et) => [
                        'icon' => $et->icon,
                        'iconColor' => $et->color,
                        'label' => Craft::t('site', $et->name),
                        'value' => $et->id,
                    ], $entryTypes),
                    'disabled' => $static,
                    'attribute' => 'typeId',
                    'errors' => $this->getErrors('typeId'),
                ]);
            })();
        }

        // Slug
        if ($this->getType()->showSlugField) {
            $fields[] = $this->slugFieldHtml($static);
        }

        // Parent
        if ($section?->type === Section::TYPE_STRUCTURE && $section->maxLevels !== 1) {
            $fields[] = (function() use ($static, $section) {
                if ($parentId = $this->getParentId()) {
                    $parent = Craft::$app->getEntries()->getEntryById($parentId, $this->siteId, [
                        'drafts' => null,
                        'draftOf' => false,
                    ]);
                } else {
                    // If the entry already has structure data, use it. Otherwise, use its canonical entry
                    /** @var self|null $parent */
                    $parent = self::find()
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
                    'describedBy' => 'parentId-label',
                    'errors' => $this->getErrors('parentId'),
                ]);
            })();
        }

        if ($section && $section->type !== Section::TYPE_SINGLE) {
            // Author
            if (Craft::$app->edition !== CmsEdition::Solo && $user->can("viewPeerEntries:$section->uid")) {
                $fields[] = (function() use ($static, $section) {
                    $authors = $this->getAuthors();
                    $html = Cp::elementSelectFieldHtml([
                        'status' => $this->getAttributeStatus('authorIds'),
                        'label' => Craft::t('app', '{max, plural, =1{Author} other {Authors}}', [
                            'max' => $section->maxAuthors,
                        ]),
                        'id' => 'authorIds',
                        'name' => 'authorIds',
                        'elementType' => User::class,
                        'selectionLabel' => Craft::t('app', 'Choose'),
                        'criteria' => [
                            'can' => "viewEntries:$section->uid",
                        ],
                        'single' => false,
                        'elements' => $authors ?: null,
                        'disabled' => $static,
                        'errors' => $this->getErrors('authorIds'),
                        'limit' => $section->maxAuthors,
                    ]);
                    return $html;
                })();
            }

            $isDeltaRegistrationActive = $view->getIsDeltaRegistrationActive();
            $view->setIsDeltaRegistrationActive(true);
            $view->registerDeltaName('postDate');
            $view->registerDeltaName('expiryDate');
            $view->setIsDeltaRegistrationActive($isDeltaRegistrationActive);

            // Post Date
            $fields[] = Cp::dateTimeFieldHtml([
                'status' => $this->getAttributeStatus('postDate'),
                'label' => Craft::t('app', 'Post Date'),
                'id' => 'postDate',
                'name' => 'postDate',
                'value' => $this->_userPostDate(),
                'errors' => $this->getErrors('postDate'),
                'disabled' => $static,
            ]);

            // Expiry Date
            $fields[] = Cp::dateTimeFieldHtml([
                'status' => $this->getAttributeStatus('expiryDate'),
                'label' => Craft::t('app', 'Expiry Date'),
                'id' => 'expiryDate',
                'name' => 'expiryDate',
                'value' => $this->expiryDate,
                'errors' => $this->getErrors('expiryDate'),
                'disabled' => $static,
            ]);
        }

        $fields[] = parent::metaFieldsHtml($static);

        return implode("\n", $fields);
    }

    /**
     * @inheritdoc
     */
    public function showStatusField(): bool
    {
        try {
            $showStatusField = $this->getType()->showStatusField;
        } catch (InvalidConfigException $e) {
            $showStatusField = true;
        }

        return $showStatusField;
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

        // Fire a 'defineParentSelectionCriteria' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_PARENT_SELECTION_CRITERIA)) {
            $event = new ElementCriteriaEvent(['criteria' => $parentOptionCriteria]);
            $this->trigger(self::EVENT_DEFINE_PARENT_SELECTION_CRITERIA, $event);
            return $event->criteria;
        }

        return $parentOptionCriteria;
    }

    /**
     * Updates the entry’s title, if its entry type has a dynamic title format.
     *
     * @since 3.0.3
     */
    public function updateTitle(): void
    {
        $entryType = $this->getType();

        if ($entryType->hasTitleField) {
            return;
        }

        if (!$entryType->titleFormat) {
            $this->title = null;
            return;
        }

        // Make sure that the locale has been loaded in case the title format has any Date/Time fields
        Craft::$app->getLocale();
        // Set Craft to the entry’s site’s language, in case the title format has any static translations
        $language = Craft::$app->language;
        $locale = Craft::$app->getLocale();
        $formattingLocale = Craft::$app->getFormattingLocale();
        $site = $this->getSite();
        $tempLocale = Craft::$app->getI18n()->getLocaleById($site->language);
        Craft::$app->language = $site->language;
        Craft::$app->set('locale', $tempLocale);
        Craft::$app->set('formattingLocale', $tempLocale);
        $title = Craft::$app->getView()->renderObjectTemplate($entryType->titleFormat, $this);
        if ($title !== '') {
            $this->title = $title;
        }
        Craft::$app->language = $language;
        Craft::$app->set('locale', $locale);
        Craft::$app->set('formattingLocale', $formattingLocale);
    }

    /**
     * Returns the Post Date value that should be shown on the edit form.
     *
     * @return DateTime|null
     */
    private function _userPostDate(): ?DateTime
    {
        if (!$this->postDate || ($this->getIsUnpublishedDraft() && $this->postDate == $this->dateCreated)) {
            // Pretend the post date hasn't been set yet, even if it has
            return null;
        }

        return $this->postDate;
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function beforeValidate(): bool
    {
        if (
            (!isset($this->_authorIds) || empty($this->_authorIds)) &&
            !isset($this->fieldId) &&
            $this->getSection()->type !== Section::TYPE_SINGLE
        ) {
            $user = Craft::$app->getUser()->getIdentity();
            if ($user) {
                $this->setAuthor($user);
            }
        }

        if (
            !$this->_userPostDate() &&
            (
                in_array($this->scenario, [self::SCENARIO_LIVE, self::SCENARIO_DEFAULT]) ||
                (!$this->getIsDraft() && !$this->getIsRevision())
            )
        ) {
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
        if ($section) {
            // Make sure the entry has at least one revision if the section has versioning enabled
            if ($this->_shouldSaveRevision()) {
                $hasRevisions = self::find()
                    ->revisionOf($this)
                    ->site('*')
                    ->status(null)
                    ->exists();
                if (!$hasRevisions) {
                    /** @var self|null $currentEntry */
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
        }

        $this->updateTitle();

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function afterSave(bool $isNew): void
    {
        if (!$this->propagating) {
            $section = $this->getSection();

            // Get the entry record
            if (!$isNew) {
                $record = EntryRecord::findOne($this->id);

                if (!$record) {
                    throw new InvalidConfigException("Invalid entry ID: $this->id");
                }
            } else {
                $record = new EntryRecord();
                $record->id = (int)$this->id;
            }

            $record->sectionId = $this->sectionId;
            $record->fieldId = $this->fieldId;
            $record->primaryOwnerId = $this->getPrimaryOwnerId();
            $record->typeId = $this->getTypeId();
            $record->postDate = Db::prepareDateForDb($this->postDate);
            $record->expiryDate = Db::prepareDateForDb($this->expiryDate);

            // Capture the dirty attributes from the record
            $dirtyAttributes = array_keys($record->getDirtyAttributes());

            $record->save(false);

            // save authors
            if (isset($this->_authorIds)) {
                // save & add to dirty attributes
                $this->_saveAuthors();

                if (isset($this->_oldAuthorIds) && $this->_authorIds !== $this->_oldAuthorIds) {
                    $dirtyAttributes[] = 'authorIds';
                }
            }

            // ownerId will be null when creating a revision
            $ownerId = $this->getOwnerId();
            if (isset($this->fieldId) && $ownerId && $this->saveOwnership) {
                if (!isset($this->sortOrder) && (!$isNew || $this->duplicateOf)) {
                    // figure out if we should proceed this way
                    // if we're dealing with an element that's being duplicated, and it has a draftId
                    // it means we're creating a draft of something
                    // if we're duplicating element via duplicate action - draftId would be empty
                    $elementId = null;
                    if ($this->duplicateOf) {
                        if ($this->draftId) {
                            $elementId = $this->duplicateOf->id;
                        }
                    } else {
                        // if we're not duplicating - use element's id
                        $elementId = $this->id;
                    }
                    if ($elementId) {
                        $this->sortOrder = (new Query())
                            ->select('sortOrder')
                            ->from(Table::ELEMENTS_OWNERS)
                            ->where([
                                'elementId' => $elementId,
                                'ownerId' => $ownerId,
                            ])
                            ->scalar() ?: null;
                    }
                }
                if (!isset($this->sortOrder)) {
                    $max = (new Query())
                        ->from(['eo' => Table::ELEMENTS_OWNERS])
                        ->innerJoin(['e' => Table::ENTRIES], '[[e.id]] = [[eo.elementId]]')
                        ->where([
                            'eo.ownerId' => $ownerId,
                            'e.fieldId' => $this->fieldId,
                        ])
                        ->max('[[eo.sortOrder]]');
                    $this->sortOrder = $max ? $max + 1 : 1;
                }
                if ($isNew) {
                    Db::insert(Table::ELEMENTS_OWNERS, [
                        'elementId' => $this->id,
                        'ownerId' => $ownerId,
                        'sortOrder' => $this->sortOrder,
                    ]);
                } else {
                    Db::update(Table::ELEMENTS_OWNERS, [
                        'sortOrder' => $this->sortOrder,
                    ], [
                        'elementId' => $this->id,
                        'ownerId' => $ownerId,
                    ]);
                }
            }

            if ($this->getIsCanonical() && isset($this->sectionId) && $section->type == Section::TYPE_STRUCTURE) {
                // Has the parent changed?
                if ($this->hasNewParent()) {
                    $this->_placeInStructure($isNew, $section);
                }

                // Update the entry’s descendants, who may be using this entry’s URI in their own URIs
                if (!$isNew) {
                    Craft::$app->getElements()->updateDescendantSlugsAndUris($this, true, true);
                }
            }

            $this->setDirtyAttributes($dirtyAttributes);
        }

        parent::afterSave($isNew);
    }

    /**
     * Save authors
     *
     * @return void
     * @throws Throwable
     * @throws \yii\db\Exception
     */
    private function _saveAuthors(): void
    {
        Db::delete(Table::ENTRIES_AUTHORS, ['entryId' => $this->id]);

        if (!empty($this->_authorIds)) {
            $data = [];
            foreach ($this->getAuthorIds() as $sortOrder => $authorId) {
                $data[] = [$this->id, $authorId, $sortOrder + 1];
            }
            Db::batchInsert(Table::ENTRIES_AUTHORS, ['entryId', 'authorId', 'sortOrder'], $data);
        }
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
        if ($section?->type === Section::TYPE_STRUCTURE) {
            // Add the entry back into its structure
            /** @var self|null $parent */
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
                /** @var self[] $drafts */
                $drafts = self::find()
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
            $this->getSection()?->enableVersioning
        );
    }

    /**
     * Check if current typeId is in the array of passed in entry types.
     * If no entry types are passed, check get all the available ones.
     *
     * @param array|null $entryTypes
     * @return bool
     * @throws InvalidConfigException
     */
    private function isEntryTypeAllowed(array|null $entryTypes = null): bool
    {
        if ($entryTypes === null) {
            $entryTypes = $this->getAvailableEntryTypes();
        }

        return in_array($this->typeId, array_map(fn($entryType) => $entryType->id, $entryTypes));
    }
}
