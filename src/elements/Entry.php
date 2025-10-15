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
use craft\base\ElementContainerFieldInterface;
use craft\base\ElementInterface;
use craft\base\ExpirableElementInterface;
use craft\base\Field;
use craft\base\FieldInterface;
use craft\base\Iconic;
use craft\base\NestedElementInterface;
use craft\base\NestedElementTrait;
use craft\behaviors\DraftBehavior;
use craft\controllers\ElementIndexesController;
use craft\db\Connection;
use craft\db\FixedOrderExpression;
use craft\db\Query;
use craft\db\Table;
use craft\elements\actions\Copy;
use craft\elements\actions\Delete;
use craft\elements\actions\DeleteForSite;
use craft\elements\actions\Duplicate;
use craft\elements\actions\MoveToSection;
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
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\fields\Matrix;
use craft\gql\interfaces\elements\Entry as EntryInterface;
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
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Throwable;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
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
                            'section-id' => $section->id,
                            'entry-type-ids' => array_map(fn(EntryType $entryType) => $entryType->id, $section->getEntryTypes()),
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
        if ($source === '*') {
            $sections = Craft::$app->getEntries()->getAllSections();
        } elseif ($source === 'singles') {
            $sections = Craft::$app->getEntries()->getSectionsByType(Section::TYPE_SINGLE);
        } elseif ($source !== null && preg_match('/^section:(.+)$/', $source, $matches)) {
            $sections = array_filter([
                Craft::$app->getEntries()->getSectionByUid($matches[1]),
            ]);
        }

        if (isset($sections)) {
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

            if (
                $user->can("createEntries:$section->uid") &&
                $user->can("saveEntries:$section->uid")
            ) {
                // Duplicate
                $actions[] = Duplicate::class;

                if ($section->type === Section::TYPE_STRUCTURE && $section->maxLevels != 1) {
                    $actions[] = [
                        'type' => Duplicate::class,
                        'deep' => true,
                    ];
                }

                // Copy
                $actions[] = Copy::class;

                // Move to section
                $actions[] = MoveToSection::class;
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
            }
        } else {
            $actions[] = Copy::class;
        }

        if (
            (
                $section &&
                $section->propagationMethod === PropagationMethod::Custom &&
                $section->getHasMultiSiteEntries() &&
                $user->can("deleteEntriesForSite:$section->uid")
            ) ||
            (
                !$section &&
                str_starts_with($source, 'custom:') &&
                Craft::$app->getIsMultiSite() &&
                Collection::make(Craft::$app->getEntries()->getEditableSections())
                    ->contains(fn(Section $section) => $section->propagationMethod === PropagationMethod::Custom)
            )
        ) {
            $actions[] = DeleteForSite::class;
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
    public static function baseBulkDuplicateAttributes(): array
    {
        return [
            ...parent::baseBulkDuplicateAttributes(),
            'sectionId' => null,
        ];
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
    protected static function defineCardAttributes(): array
    {
        $currentUser = Craft::$app->getUser()->getIdentity();

        $attributes = array_merge(parent::defineCardAttributes(), [
            'section' => [
                'label' => Craft::t('app', 'Section'),
                'placeholder' => fn() => Craft::t('app', 'Section'),
            ],
            'type' => [
                'label' => Craft::t('app', 'Entry Type'),
                'placeholder' => fn() => Craft::t('app', 'Entry Type'),
            ],
            'authors' => [
                'label' => Craft::t('app', 'Authors'),
                'placeholder' => fn() => $currentUser ? Cp::elementChipHtml($currentUser) : '',
            ],
            'parent' => [
                'label' => Craft::t('app', 'Parent'),
                'placeholder' => fn() => Html::tag(
                    'span',
                    Craft::t('app', 'Parent {type} Title', ['type' => self::displayName()]),
                    ['class' => 'card-placeholder'],
                ),
            ],
            'postDate' => [
                'label' => Craft::t('app', 'Post Date'),
                'placeholder' => fn() => (new \DateTime())->sub(new \DateInterval('P15D')),
            ],
            'expiryDate' => [
                'label' => Craft::t('app', 'Expiry Date'),
                'placeholder' => fn() => (new \DateTime())->add(new \DateInterval('P15D')),
            ],
            'revisionNotes' => [
                'label' => Craft::t('app', 'Revision Notes'),
                'placeholder' => fn() => Craft::t('app', 'Revision Notes'),
            ],
            'revisionCreator' => [
                'label' => Craft::t('app', 'Last Edited By'),
                'placeholder' => fn() => $currentUser ? Cp::elementChipHtml($currentUser) : '',
            ],
            'drafts' => [
                'label' => Craft::t('app', 'Drafts'),
                'placeholder' => fn() => Html::tag(
                    'span',
                    Craft::t('app', 'Draft {num}', ['num' => 1]),
                    ['class' => 'card-placeholder'],
                ),
            ],
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
    public static function attributePreviewHtml(array $attribute): mixed
    {
        return match ($attribute['value']) {
            'authors', 'parent', 'revisionCreator', 'drafts' => $attribute['placeholder'],
            default => parent::attributePreviewHtml($attribute),
        };
    }

    /**
     * @inheritdoc
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle): array|null|false
    {
        switch ($handle) {
            case 'author':
            case 'authors':
                $entryIds = array_map(fn(ElementInterface $entry) => $entry->id, $sourceElements);
                $map = (new Query())
                    ->select([
                        'source' => 'entryId',
                        'target' => 'authorId',
                    ])
                    ->from(Table::ENTRIES_AUTHORS)
                    ->where(['entryId' => $entryIds])
                    ->orderBy(['sortOrder' => SORT_ASC])
                    ->all();

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
        // Don't use override data
        $entryType = $entryType->original ?? $entryType;

        return sprintf('%s_Entry', $entryType->handle);
    }

    /**
     * @inheritdoc
     */
    public static function baseGqlType(): Type
    {
        return EntryInterface::getType();
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
     * @var self::STATUS_*|null The entry’s previous status, if it had one
     */
    public ?string $oldStatus = null;

    /**
     * @var self::STATUS_LIVE|self::STATUS_PENDING|self::STATUS_EXPIRED
     */
    private string $status;

    /**
     * @var bool Whether the entry was deleted along with its entry type
     * @see beforeDelete()
     * @internal
     */
    public bool $deletedWithEntryType = false;

    /**
     * @var bool Whether the entry was deleted along with its section
     * @see beforeDelete()
     * @internal
     */
    public bool $deletedWithSection = false;

    /**
     * @var bool Whether to force-place the entry within its structure.
     * @since 5.7.0
     */
    public bool $placeInStructure = false;

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
     * @var EntryType|null Entry Type
     * @see getType()
     */
    private ?EntryType $_type = null;

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function init(): void
    {
        parent::init();
        if (isset($this->id)) {
            $this->oldStatus = $this->getStatus();
        }
        $this->_oldTypeId = $this->_typeId;
    }

    /**
     * @inheritdoc
     */
    public function attributes(): array
    {
        $names = array_flip($this->traitAttributes());
        unset($names['deletedWithEntryType']);
        unset($names['deletedWithSection']);
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
                'max' => $this->getSection()->maxAuthors ?? PHP_INT_MAX,
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
        $rules[] = [['placeInStructure'], 'safe'];
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
            'on' => self::SCENARIO_LIVE,
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
        if ($section && $section->type !== Section::TYPE_SINGLE && $section->maxAuthors !== 0) {
            $rules[] = [['authorIds'], 'required', 'on' => self::SCENARIO_LIVE];
            if (isset($section->maxAuthors)) {
                $rules[] = [
                    ['authorIds'],
                    ArrayValidator::class,
                    'max' => $section->maxAuthors,
                    'tooMany' => Craft::t('app', '{num, plural, =1{Only one author is} other{Up to {num, number} authors are}} allowed.', [
                        'num' => $section->maxAuthors,
                    ]),
                ];
            }
        }

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function setAttributesFromRequest(array $values): void
    {
        parent::setAttributesFromRequest($values);

        // Did the entry type just change?
        if (isset($this->_typeId, $this->_oldTypeId) && $this->_typeId !== $this->_oldTypeId) {
            $this->handleChangedTypeId();
        }
    }

    /**
     * @inheritdoc
     */
    protected function shouldValidateTitle(): bool
    {
        $entryType = $this->getType();
        if (!$entryType->hasTitleField) {
            return false;
        }
        try {
            /** @var EntryTitleField $titleField */
            $titleField = $entryType->getFieldLayout()->getField('title');
        } catch (InvalidArgumentException) {
            return true;
        }

        return $titleField->required && $titleField->showInForm($this);
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

        $crumbs = [
            [
                'label' => Craft::t('app', 'Entries'),
                'url' => 'entries',
            ],
        ];

        // If the section’s source is disabled, just show its name w/o a link
        $sourceKey = $section->type === Section::TYPE_SINGLE ? 'singles' : "section:$section->uid";
        if (Craft::$app->getElementSources()->sourceExists(Entry::class, $sourceKey)) {
            $sections = Collection::make(Craft::$app->getEntries()->getEditableSections());
            $requestedSite = Cp::requestedSite();
            if ($requestedSite) {
                $sections = $sections->filter(fn(Section $s) => in_array($requestedSite->id, $s->getSiteIds()));
            }
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

            $crumbs[] = [
                'menu' => [
                    'label' => Craft::t('app', 'Select section'),
                    'items' => $sectionOptions->all(),
                ],
            ];
        } else {
            $crumbs[] = [
                'label' => Craft::t('site', $section->name),
            ];
        }

        if ($section->type === Section::TYPE_STRUCTURE) {
            $elementsService = Craft::$app->getElements();
            $user = Craft::$app->getUser()->getIdentity();

            $ancestors = $this->getAncestors();
            if ($ancestors instanceof ElementQueryInterface) {
                $ancestors->status(null);
            }

            foreach ($ancestors->all() as $ancestor) {
                if ($elementsService->canView($ancestor, $user)) {
                    $crumbs[] = [
                        'html' => Cp::elementChipHtml($ancestor, ['class' => 'chromeless']),
                    ];
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
            $section = $this->getSection();
            if ($section?->type === Section::TYPE_SINGLE) {
                return $section->getUiLabel();
            }
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
    public function showStatusIndicator(): bool
    {
        return $this->getType()->showStatusField;
    }

    /**
     * @inheritdoc
     */
    public function getCardTitle(): ?string
    {
        return $this->getType()->getUiLabel();
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
        }, $this->getSection()->previewTargets ?? []);
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
        try {
            return $this->getType()->getFieldLayout();
        } catch (InvalidConfigException) {
            // The entry type was probably deleted
            return null;
        }
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
        $this->_type = null;
        $this->fieldLayoutId = null;
    }

    /**
     * Returns the available entry types for the entry.
     *
     * @param bool $triggerEvent
     * @return EntryType[]
     * @throws InvalidConfigException
     * @since 3.6.0
     */
    public function getAvailableEntryTypes(bool $triggerEvent = true): array
    {
        if (isset($this->fieldId)) {
            /** @var EntryType[] $entryTypes */
            $entryTypes = $this->getField()->getFieldLayoutProviders();
        } elseif (isset($this->sectionId)) {
            $entryTypes = $this->getSection()->getEntryTypes();
        } else {
            throw new InvalidConfigException('Either `sectionId` or `fieldId` + `ownerId` must be set on the entry.');
        }

        // Fire a 'defineEntryTypes' event
        if ($triggerEvent && $this->hasEventHandlers(self::EVENT_DEFINE_ENTRY_TYPES)) {
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
        if (!isset($this->_type)) {
            if (isset($this->_typeId)) {
                $entryType = ArrayHelper::firstWhere(
                    $this->getAvailableEntryTypes(false),
                    fn(EntryType $entryType) => $entryType->id === $this->_typeId,
                );
                if (!$entryType) {
                    // Maybe the section/field no longer allows this type,
                    // so get it directly from the Entries service instead
                    $entryType = Craft::$app->getEntries()->getEntryTypeById($this->_typeId, true);
                    if (!$entryType) {
                        throw new InvalidConfigException("Invalid entry type ID: $this->_typeId");
                    }
                }
            } else {
                // Default to the section/field's first entry type
                $entryType = ArrayHelper::firstValue($this->getAvailableEntryTypes());
                if (!$entryType) {
                    throw new InvalidConfigException('Entry is missing its type ID');
                }
            }
            $this->_type = $entryType;
        }

        return $this->_type;
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
            if (!isset($this->sectionId)) {
                $authors = [];
            } elseif (isset($this->_authorIds)) {
                $authors = User::find()
                    ->id($this->_authorIds)
                    ->fixedOrder()
                    ->status(null)
                    ->all();
            } else {
                if (isset($this->elementQueryResult) && count($this->elementQueryResult) > 1) {
                    // eager-load authors for all queried entries
                    Craft::$app->getElements()->eagerLoadElements(self::class, $this->elementQueryResult, ['authors']);
                    return $this->_authors ?? [];
                }

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

        if ($status !== self::STATUS_ENABLED) {
            return $status;
        }

        return $this->status ?? $this->_status();
    }

    /**
     * @return self::STATUS_LIVE|self::STATUS_PENDING|self::STATUS_EXPIRED
     */
    private function _status(): string
    {
        $now = DateTimeHelper::now();
        return match (true) {
            !$this->postDate || $this->postDate > $now => self::STATUS_PENDING,
            $this->expiryDate && $this->expiryDate <= $now => self::STATUS_EXPIRED,
            default => self::STATUS_LIVE,
        };
    }

    /**
     * Sets the status, if it’s stored statically.
     *
     * @param self::STATUS_LIVE|self::STATUS_PENDING|self::STATUS_EXPIRED $status
     * @since 5.7.0
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
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
            'ownerId' => $this->getOwnerId(),
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
            /**
             * @var static|DraftBehavior $this
             * @phpstan-ignore-next-line
             */
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
            /**
             * @var static|DraftBehavior $this
             * @phpstan-ignore-next-line
             */
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
    public function canCopy(User $user): bool
    {
        return Craft::$app->getElements()->canView($this, $user);
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
            /**
             * @var static|DraftBehavior $this
             * @phpstan-ignore-next-line
             */
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

        if ($section->propagationMethod === PropagationMethod::Custom) {
            if ($this->getIsDraft()) {
                /**
                 * @var static|DraftBehavior $this
                 * @phpstan-ignore varTag.nativeType
                 */
                return (
                    $this->creatorId === $user->id ||
                    $user->can("deletePeerEntryDrafts:$section->uid")
                );
            }

            if (!$user->can("deleteEntriesForSite:$section->uid")) {
                return false;
            }

            return (
                in_array($user->id, $this->getAuthorIds(), true) ||
                $user->can("deletePeerEntriesForSite:$section->uid")
            );
        }

        return false;
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
        $section = $this->getSection();
        if ($section) {
            return $section->enableVersioning;
        }

        $field = $this->getField();
        return $field instanceof Matrix && $field->enableVersioning;
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
     */
    protected function safeActionMenuItems(): array
    {
        $actions = parent::safeActionMenuItems();

        if (
            Craft::$app->getUser()->getIsAdmin() &&
            Craft::$app->getConfig()->getGeneral()->allowAdminChanges
        ) {
            // Entry type settings
            $entryTypeEditId = sprintf('edit-entry-type-%s', mt_rand());
            $actions[] = [
                'id' => $entryTypeEditId,
                'icon' => 'gear',
                'label' => Craft::t('app', 'Entry type settings'),
            ];

            $view = Craft::$app->getView();
            $view->registerJsWithVars(fn($id, $params, $isNestedEntry) => <<<JS
(() => {
  $('#' + $id).on('activate', function() {
    const params = $params;
    if (!$isNestedEntry) {
        const input = $(this).closest('.menu').data('disclosureMenu').\$trigger.closest('form').find('.entry-type-select').find('input');
        if (input.length) {
          params.entryTypeId = input.val();
        }
    }
    new Craft.CpScreenSlideout('entry-types/edit', {params});
  });
})();
JS, [
                $view->namespaceInputId($entryTypeEditId),
                ['entryTypeId' => $this->typeId],
                isset($this->fieldId),
            ]);

            // Section settings
            if (!empty($this->sectionId)) {
                $sectionEditId = sprintf('edit-section-%s', mt_rand());
                $actions[] = [
                    'id' => $sectionEditId,
                    'icon' => 'gear',
                    'label' => Craft::t('app', 'Section settings'),
                ];

                $view = Craft::$app->getView();
                $view->registerJsWithVars(fn($id, $params) => <<<JS
    (() => {
      $('#' + $id).on('activate', function() {
        new Craft.CpScreenSlideout('sections/edit-section', {params: $params});
      });
    })();
    JS, [
                    $view->namespaceInputId($sectionEditId),
                    ['sectionId' => $this->sectionId],
                ]);
            }
        }

        return $actions;
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
                if (!$section) {
                    return '';
                }
                return Cp::chipHtml($section, [
                    'class' => 'chromeless',
                    'showThumb' => false,
                ]);
            case 'type':
                try {
                    return Cp::chipHtml($this->getType(), [
                        'class' => 'chromeless',
                        'showThumb' => $this->viewMode !== 'cards',
                    ]);
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
    protected function inlineAttributeInputHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'postDate':
                return Cp::dateTimeFieldHtml([
                    'name' => 'postDate',
                    'value' => $this->postDate,
                ]);
            case 'expiryDate':
                return Cp::dateTimeFieldHtml([
                    'name' => 'expiryDate',
                    'value' => $this->expiryDate,
                ]);
            case 'slug':
                return Cp::textHtml([
                    'name' => 'slug',
                    'value' => $this->slug,
                ]);
            case 'authors':
                $authors = $this->getAuthors();
                $section = $this->getSection();
                return Cp::elementSelectHtml([
                    'status' => $this->getAttributeStatus('authorIds'),
                    'label' => Craft::t('app', '{max, plural, =1{Author} other {Authors}}', [
                        'max' => $section->maxAuthors ?? PHP_INT_MAX,
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
                    'disabled' => false,
                    'errors' => $this->getErrors('authorIds'),
                    'limit' => $section->maxAuthors,
                ]);
            default:
                return parent::inlineAttributeInputHtml($attribute);
        }
    }

    /**
     * @inheritdoc
     */
    protected function htmlAttributes(string $context): array
    {
        return [
            'data' => [
                'entry-type-id' => $this->getType()->id,
                'movable' => $this->_canMove(),
            ],
        ];
    }

    /**
     * Returns whether the given user is authorized to move this entry.
     */
    private function _canMove(?User $user = null): bool
    {
        if (!$user) {
            $user = Craft::$app->getUser()->getIdentity();
            if (!$user) {
                return false;
            }
        }

        $section = $this->getSection();

        if (!$section) {
            return false;
        }

        // disallow moving singles and trashed entries
        if ($section->type === Section::TYPE_SINGLE || $this->trashed) {
            return false;
        }

        // if there aren't any compatible sections, just don't bother with further checks
        if (!$this->_moveCompatibleSectionsCount()) {
            return false;
        }

        if ($this->getIsDraft()) {
            /**
             * @var static|DraftBehavior $this
             * @phpstan-ignore-next-line
             */
            return (
                $this->creatorId === $user->id ||
                $user->can("savePeerEntryDrafts:$section->uid")
            );
        }

        if (!$user->can("saveEntries:$section->uid")) {
            return false;
        }

        return (
            in_array($user->id, $this->getAuthorIds(), true) ||
            $user->can("savePeerEntries:$section->uid")
        );
    }

    /**
     * Get sections that this entry could be moved to - sections that use the exact same entry type.
     */
    private function _moveCompatibleSectionsCount(): int
    {
        // get entry type id
        $entryTypeId = $this->getTypeId();

        // get sections all editable sections without singles and without the section this entry belongs to
        // get all entry types for them
        $sections = Collection::make(Craft::$app->getEntries()->getEditableSections())
            ->filter(fn(Section $s) => $s->type !== Section::TYPE_SINGLE && $s->id !== $this->sectionId)
            ->map(fn(Section $s) => [
                'entryTypes' => $s->getEntryTypes(),
            ]);

        // get sections that use the same entry type as this entry
        $compatibleSections = $sections
            ->filter(fn(array $s) => ArrayHelper::contains($s['entryTypes'], 'id', $entryTypeId));

        return $compatibleSections->count();
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

        $this->_applyActionBtnEntryTypeCompatibility();

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
                'fieldClass' => 'entry-type-select',
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
            if (
                $section->maxAuthors !== 0 &&
                Craft::$app->edition !== CmsEdition::Solo &&
                $user->can("viewPeerEntries:$section->uid")
            ) {
                $fields[] = (function() use ($static, $section) {
                    $authors = $this->getAuthors();
                    $html = Cp::elementSelectFieldHtml([
                        'status' => $this->getAttributeStatus('authorIds'),
                        'label' => Craft::t('app', '{max, plural, =1{Author} other {Authors}}', [
                            'max' => $section->maxAuthors ?? PHP_INT_MAX,
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
     * Checks if the "Apply Draft" and "Revert to a revision" buttons should be disabled and if so
     * applies the tooltip message.
     *
     * @return void
     * @throws InvalidConfigException
     */
    private function _applyActionBtnEntryTypeCompatibility(): void
    {
        $draftMessage = Craft::t(
            'app',
            'This draft’s entry type is no longer available. You can still view it, but not apply it.'
        );
        $revisionMessage = Craft::t(
            'app',
            'This revision’s entry type is no longer available. You can still view it, but not revert to it.'
        );

        if (!$this->isEntryTypeCompatible()) {
            $js = <<<JS
let applyDraftBtn = $('#action-buttons .tooltip-draft-btn')
if (applyDraftBtn.length > 0) {
  applyDraftBtn.addClass('disabled');
  let tooltipBtn = `<craft-tooltip aria-label="$draftMessage">` +
    applyDraftBtn.get(0).outerHTML +
    `</craft-tooltip>`;
  applyDraftBtn.replaceWith(tooltipBtn);
}

let revertRevisionBtn = $('#action-buttons .revision-draft-btn');
if (revertRevisionBtn.length > 0) {
  revertRevisionBtn.addClass('disabled');
  let tooltipBtn = `<craft-tooltip aria-label="$revisionMessage">` +
    revertRevisionBtn.get(0).outerHTML +
    `</craft-tooltip>`;
  revertRevisionBtn.replaceWith(tooltipBtn);
}
JS;
            Craft::$app->getView()->registerJs($js);
        }
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

        // Leave the title alone if the layout has a Title field, and it's already set to something
        if ($entryType->hasTitleField && trim($this->title ?? '') !== '') {
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
     * @throws Exception if reasons
     */
    public function beforeSave(bool $isNew): bool
    {
        if ($this->_shouldSaveRevision()) {
            // Make sure the entry has at least one revision
            $hasRevisions = static::find()
                ->revisionOf($this)
                ->site('*')
                ->status(null)
                ->exists();

            if (!$hasRevisions) {
                /** @var self|null $current */
                $current = static::find()
                    ->id($this->id)
                    ->site('*')
                    ->status(null)
                    ->one();

                // May be null if the entry is currently stored as an unpublished draft
                if ($current) {
                    Craft::$app->getRevisions()->createRevision(
                        $current,
                        $current->getAuthorId(),
                        sprintf('Revision from %s', Craft::$app->getFormatter()->asDatetime($current->dateUpdated)),
                    );
                }
            }
        }

        $section = $this->getSection();
        if ($section) {
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

        $this->maybeSetDefaultAttributes();

        $this->updateTitle();

        return parent::beforeSave($isNew);
    }

    /**
     * Set the default values for attributes if certain conditions are met.
     *
     * @return void
     */
    private function maybeSetDefaultAttributes(): void
    {
        // if we're resaving, we shouldn't be setting the defaults
        if ($this->resaving) {
            return;
        }

        if (
            empty($this->getAuthors()) &&
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

            // todo: update after the next breakpoint
            if (Craft::$app->getDb()->columnExists(Table::ENTRIES, 'status')) {
                $status = $this->_status();
                $record->status = $status;
                // only update $this->status if it's already set, indicating that staticStatuses is enabled
                if (isset($this->status)) {
                    $this->status = $status;
                }
            }

            // Capture the dirty attributes from the record
            $dirtyAttributes = array_keys($record->getDirtyAttributes());
            $record->save(false);

            // save authors
            if (isset($this->sectionId) && isset($this->_authorIds)) {
                // save & add to dirty attributes
                $this->_saveAuthors();

                if (isset($this->_oldAuthorIds) && $this->_authorIds !== $this->_oldAuthorIds) {
                    $dirtyAttributes[] = 'authorIds';
                }
            }

            $this->setDirtyAttributes($dirtyAttributes);

            $this->saveOwnership($isNew, Table::ENTRIES);

            if (
                (!$this->duplicateOf || $this->updatingFromDerivative || $this->placeInStructure) &&
                isset($this->sectionId) &&
                $section->type == Section::TYPE_STRUCTURE
            ) {
                // Has the parent changed?
                if ($this->placeInStructure || $this->hasNewParent()) {
                    $this->_placeInStructure($isNew, $section);
                }

                // Update the entry’s descendants, who may be using this entry’s URI in their own URIs
                if (!$isNew && $this->getIsCanonical()) {
                    Craft::$app->getElements()->updateDescendantSlugsAndUris($this, true, true);
                }
            }
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
        if (!isset($this->_oldAuthorIds)) {
            // Don't trust $this->_authors/_authorIds, as it may have been set to the updated value
            $oldAuthorIds = (new Query())
                ->select('authorId')
                ->from(Table::ENTRIES_AUTHORS)
                ->where(['entryId' => $this->duplicateOf->id ?? $this->id])
                ->orderBy(['sortOrder' => SORT_ASC])
                ->column();
            $this->_oldAuthorIds = array_map(fn($id) => (int)$id, $oldAuthorIds);
        }

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
                ->site('*')
                ->unique()
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
            'deletedWithSection' => $this->deletedWithSection,
            'parentId' => null,
        ];

        if ($this->structureId) {
            // Remember the parent ID, in case the entry needs to be restored later
            $parentId = $this->ancestors()
                ->ancestorDist(1)
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
        $this->deletedWithEntryType = false;
        $this->deletedWithSection = false;
        Db::update(Table::ENTRIES, [
            'deletedWithEntryType' => null,
            'deletedWithSection' => null,
        ], ['id' => $this->id]);

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
            $this->hasRevisions()
        );
    }

    /**
     * Returns whether the entry’s type is allowed in its section.
     *
     * @return bool
     * @throws InvalidConfigException
     * @since 5.3.0
     */
    public function isEntryTypeCompatible(): bool
    {
        $section = $this->getSection();

        // if entry doesn't belong to a section (is nested) just allow it
        if (!$section) {
            return true;
        }

        $sectionEntryTypes = Collection::make($section->getEntryTypes())
            ->map(fn(EntryType $et) => $et->id)
            ->all();

        return in_array($this->getTypeId(), $sectionEntryTypes);
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

    private function handleChangedTypeId(): void
    {
        $oldLayout = Craft::$app->getEntries()->getEntryTypeById($this->_oldTypeId)?->getFieldLayout();
        if (!$oldLayout) {
            return;
        }

        $newFields = $this->getType()->getFieldLayout()->getCustomFields();
        $oldFields = Arr::keyBy($oldLayout->getCustomFields(), fn(FieldInterface $field) => $field->handle);
        $fieldsService = Craft::$app->getFields();

        foreach ($newFields as $newField) {
            if (isset($oldFields[$newField->handle])) {
                $oldField = $oldFields[$newField->handle];
                if (
                    !$fieldsService->areFieldTypesCompatible($newField::class, $oldField::class) ||
                    (
                        (
                            $newField instanceof ElementContainerFieldInterface ||
                            $oldField instanceof ElementContainerFieldInterface
                        ) &&
                        $newField->id !== $oldField->id
                    )
                ) {
                    $this->setFieldValue($newField->handle, null);
                }
            }
        }
    }

    protected function partialTemplatePathCandidates(): array
    {
        $templates = parent::partialTemplatePathCandidates();

        $entryType = $this->getType();
        if (isset($entryType->original) && $entryType->original->handle !== $entryType->handle) {
            $templates[] = [
                'template' => sprintf(
                    '%s/%s/%s',
                    Craft::$app->getConfig()->getGeneral()->partialTemplatesPath,
                    static::refHandle(),
                    $entryType->original->handle,
                ),
                'priority' => 5,
            ];
        }

        return $templates;
    }
}
