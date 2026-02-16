<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Elements;

use Craft;
use craft\base\ElementInterface;
use craft\base\ExpirableElementInterface;
use craft\base\NestedElementInterface;
use craft\base\NestedElementTrait;
use craft\controllers\ElementIndexesController;
use craft\controllers\ElementsController;
use craft\elements\actions\Copy;
use craft\elements\actions\Delete;
use craft\elements\actions\DeleteForSite;
use craft\elements\actions\Duplicate;
use craft\elements\actions\MoveToSection;
use craft\elements\actions\NewChild;
use craft\elements\actions\NewSiblingAfter;
use craft\elements\actions\NewSiblingBefore;
use craft\elements\actions\Restore;
use craft\elements\conditions\entries\EntryCondition;
use craft\elements\conditions\entries\SectionConditionRule;
use craft\elements\conditions\entries\TypeConditionRule;
use craft\elements\db\EagerLoadPlan;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db as DbHelper;
use craft\helpers\ElementHelper;
use craft\helpers\UrlHelper;
use craft\web\twig\AllowedInSandbox;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Component\Contracts\Colorable;
use CraftCms\Cms\Component\Contracts\Iconic;
use CraftCms\Cms\Database\Expressions\FixedOrderExpression;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Events\DefineEntryTypes;
use CraftCms\Cms\Entry\Events\DefineMetaFields;
use CraftCms\Cms\Entry\Events\DefineParentSelectionCriteria;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Validation\EntryRules;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\LayoutElements\entries\EntryTitleField;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\DefaultPlacement;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Structure\Enums\Mode;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\AssetRegistry;
use CraftCms\Cms\Support\Facades\Entries;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Structures;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Validation\Attributes\Ruleset;
use DateInterval;
use DateTime;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Override;
use Throwable;
use Tpetry\QueryExpressions\Language\Alias;
use yii\base\Exception;
use yii\base\InvalidConfigException;

use function CraftCms\Cms\t;

/**
 * @property int $typeId the entry type’s ID
 * @property EntryType $type the entry type
 * @property \CraftCms\Cms\Section\Data\Section|null $section the entry’s section
 * @property User|null $author the primary entry author
 * @property User[] $authors the entry authors
 * @property int|null $authorId The primary entry author’s ID
 * @property int[] $authorIds the entry authors’ IDs
 */
#[Ruleset(EntryRules::class)]
class Entry extends Element implements Colorable, ExpirableElementInterface, Iconic, NestedElementInterface
{
    use NestedElementTrait {
        eagerLoadingMap as traitEagerLoadingMap;
        attributes as traitAttributes;
        extraFields as traitExtraFields;
        setEagerLoadedElements as traitSetEagerLoadedElements;
    }

    public const string STATUS_LIVE = 'live';

    public const string STATUS_PENDING = 'pending';

    public const string STATUS_EXPIRED = 'expired';

    #[Override]
    public static function displayName(): string
    {
        return t('Entry');
    }

    #[Override]
    public static function lowerDisplayName(): string
    {
        return t('entry');
    }

    #[Override]
    public static function pluralDisplayName(): string
    {
        return t('Entries');
    }

    #[Override]
    public static function pluralLowerDisplayName(): string
    {
        return t('entries');
    }

    public static function refHandle(): string
    {
        return 'entry';
    }

    public static function hasDrafts(): bool
    {
        return true;
    }

    #[Override]
    public static function trackChanges(): bool
    {
        return true;
    }

    #[Override]
    public static function hasTitles(): bool
    {
        return true;
    }

    #[Override]
    public static function hasUris(): bool
    {
        return true;
    }

    #[Override]
    public static function isLocalized(): bool
    {
        return true;
    }

    #[Override]
    public static function hasStatuses(): bool
    {
        return true;
    }

    #[Override]
    public static function statuses(): array
    {
        return [
            self::STATUS_LIVE => t('Live'),
            self::STATUS_PENDING => t('Pending'),
            self::STATUS_EXPIRED => t('Expired'),
            self::STATUS_DISABLED => t('Disabled'),
        ];
    }

    /**
     * @return EntryQuery The newly created [[EntryQuery]] instance.
     */
    #[Override]
    public static function find(): EntryQuery
    {
        return new EntryQuery;
    }

    /**
     * @return EntryCondition
     */
    #[Override]
    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(EntryCondition::class, [self::class]);
    }

    #[Override]
    public static function multiPageSources(): bool
    {
        return true;
    }

    #[Override]
    protected static function defineSources(string $context): array
    {
        if ($context === ElementSources::CONTEXT_INDEX) {
            $sections = Sections::getEditableSections();
            $editable = true;
        } else {
            $sections = Sections::getAllSections();
            $editable = null;
        }

        $sectionIds = [];
        $singleSectionIds = [];
        $sectionsByType = [];

        foreach ($sections as $section) {
            $sectionIds[] = $section->id;

            if ($section->type === SectionType::Single) {
                $singleSectionIds[] = $section->id;
            } else {
                $sectionsByType[$section->type->value][] = $section;
            }
        }

        $sources = [
            [
                'key' => '*',
                'label' => t('All entries'),
                'criteria' => [
                    'sectionId' => $sectionIds,
                    'editable' => $editable,
                ],
                'defaultSort' => ['postDate', 'desc'],
            ],
        ];

        if (! empty($singleSectionIds)) {
            $sources[] = [
                'key' => 'singles',
                'label' => t('Singles'),
                'criteria' => [
                    'sectionId' => $singleSectionIds,
                    'editable' => $editable,
                ],
                'defaultSort' => ['title', 'asc'],
            ];
        }

        $sectionTypes = [
            SectionType::Channel->value => t('Channels'),
            SectionType::Structure->value => t('Structures'),
        ];

        $user = Auth::user();

        foreach ($sectionTypes as $type => $heading) {
            if (! empty($sectionsByType[$type])) {
                $sources[] = ['heading' => $heading];

                foreach ($sectionsByType[$type] as $section) {
                    /** @var \CraftCms\Cms\Section\Data\Section $section */
                    $source = [
                        'key' => 'section:'.$section->uid,
                        'label' => t($section->name, category: 'site'),
                        'sites' => $section->getSiteIds(),
                        'data' => [
                            'type' => $type,
                            'handle' => $section->handle,
                            'section-id' => $section->id,
                            'entry-type-ids' => array_map(fn (EntryType $entryType) => $entryType->id, $section->getEntryTypes()),
                        ],
                        'criteria' => [
                            'sectionId' => $section->id,
                            'editable' => $editable,
                        ],
                    ];

                    if ($type === SectionType::Structure->value) {
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

    #[Override]
    public static function modifyCustomSource(array $config): array
    {
        if (empty($config['condition']['conditionRules'])) {
            return $config;
        }

        // see if it's limited to one section
        /** @var SectionConditionRule|null $sectionRule */
        $sectionRule = Arr::first(
            $config['condition']['conditionRules'],
            fn (array $rule) => $rule['class'] === SectionConditionRule::class,
        );
        $sectionOptions = $sectionRule['values'] ?? null;

        if ($sectionOptions && count($sectionOptions) === 1) {
            if ($section = Sections::getSectionByUid(reset($sectionOptions))) {
                $config['data']['handle'] = $section->handle;
            }
        }

        // see if it specifies any entry types
        /** @var TypeConditionRule|null $entryTypeRule */
        $entryTypeRule = Arr::first(
            $config['condition']['conditionRules'],
            fn (array $rule) => $rule['class'] === TypeConditionRule::class,
        );
        $entryTypeOptions = $entryTypeRule['values'] ?? null;

        if ($entryTypeOptions) {
            $entryType = EntryTypes::getEntryTypeByUid(reset($entryTypeOptions));
            if ($entryType) {
                $config['data']['entry-type'] = $entryType->handle;
            }
        }

        return $config;
    }

    #[Override]
    protected static function defineFieldLayouts(?string $source): array
    {
        if ($source === '*') {
            $sections = Sections::getAllSections()->all();
        } elseif ($source === 'singles') {
            $sections = Sections::getSectionsByType(SectionType::Single);
        } elseif ($source !== null && preg_match('/^section:(.+)$/', $source, $matches)) {
            $sections = array_filter([
                Sections::getSectionByUid($matches[1]),
            ]);
        }

        if (isset($sections)) {
            $entryTypes = array_values(array_unique(array_merge(
                ...array_map(fn (Section $section) => $section->getEntryTypes(), $sections),
            )));
        } else {
            // get all entry types, including those which may only be used by Matrix fields
            $entryTypes = EntryTypes::getAllEntryTypes()->all();
        }

        return array_map(fn (EntryType $entryType) => $entryType->getFieldLayout(), $entryTypes);
    }

    #[Override]
    protected static function defineActions(string $source): array
    {
        // Get the selected site
        $controller = Craft::$app->controller;
        if ($controller instanceof ElementIndexesController) {
            $elementQuery = $controller->getElementQuery();
        } else {
            $elementQuery = null;
        }
        $site = $elementQuery && $elementQuery->siteId
            ? Sites::getSiteById($elementQuery->siteId)
            : Sites::getCurrentSite();

        // Get the section we need to check permissions on
        if (preg_match('/^section:(\d+)$/', $source, $matches)) {
            $section = Sections::getSectionById((int) $matches[1]);
        } elseif (preg_match('/^section:(.+)$/', $source, $matches)) {
            $section = Sections::getSectionByUid($matches[1]);
        } else {
            $section = null;
        }

        // Now figure out what we can do with these
        $actions = [];
        $elementsService = Craft::$app->getElements();

        if ($section) {
            $user = Auth::user();

            if (
                $section->type === SectionType::Structure &&
                $user->can('createEntries:'.$section->uid)
            ) {
                $newEntryUrl = 'entries/'.$section->handle.'/new';
                // $newEntryUrl = sprintf('%s/new', $section->getCpIndexUri());

                if (Sites::isMultiSite()) {
                    $newEntryUrl .= '?site='.$site->handle;
                }

                $actions[] = $elementsService->createAction([
                    'type' => NewSiblingBefore::class,
                    'newSiblingUrl' => $newEntryUrl,
                ]);

                $actions[] = $elementsService->createAction([
                    'type' => NewSiblingAfter::class,
                    'newSiblingUrl' => $newEntryUrl,
                ]);

                if ($section->maxLevels !== 1) {
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
                $actions[] = [
                    'type' => Duplicate::class,
                    'asDrafts' => true,
                ];

                if ($section->type === SectionType::Structure && $section->maxLevels !== 1) {
                    $actions[] = [
                        'type' => Duplicate::class,
                        'asDrafts' => true,
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
                    $section->type === SectionType::Structure &&
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
                ! $section &&
                str_starts_with($source, 'custom:') &&
                Sites::isMultiSite() &&
                Sections::getEditableSections()
                    ->contains(fn (Section $section) => $section->propagationMethod === PropagationMethod::Custom)
            )
        ) {
            $actions[] = DeleteForSite::class;
        }

        // Restore
        $actions[] = Restore::class;

        return $actions;
    }

    #[Override]
    protected static function includeSetStatusAction(): bool
    {
        return true;
    }

    #[Override]
    public static function baseBulkDuplicateAttributes(): array
    {
        return [
            ...parent::baseBulkDuplicateAttributes(),
            'sectionId' => null,
        ];
    }

    #[Override]
    protected static function defineSortOptions(): array
    {
        return [
            'title' => t('Title'),
            'slug' => t('Slug'),
            'uri' => t('URI'),
            [
                'label' => t('Section'),
                'orderBy' => function (int $dir) {
                    $sectionIds = Sections::getAllSections()
                        ->sort(fn (Section $a, Section $b) => $dir === SORT_ASC
                            ? $a->name <=> $b->name
                            : $b->name <=> $a->name)
                        ->pluck('id')
                        ->all();

                    return new FixedOrderExpression('entries.sectionId', $sectionIds);
                },
                'attribute' => 'section',
            ],
            [
                'label' => t('Entry Type'),
                'orderBy' => function (int $dir) {
                    $entryTypeIds = EntryTypes::getAllEntryTypes()
                        ->sort(fn (EntryType $a, EntryType $b) => $dir === SORT_ASC
                            ? $a->name <=> $b->name
                            : $b->name <=> $a->name)
                        ->pluck('id')
                        ->all();

                    return new FixedOrderExpression('entries.typeId', $entryTypeIds);
                },
                'attribute' => 'type',
            ],
            [
                'label' => t('Post Date'),
                'orderBy' => function (int $dir) {
                    if ($dir === SORT_ASC) {
                        if (DB::isMysql()) {
                            return DB::raw('postDate IS NOT NULL DESC, postDate ASC');
                        }

                        return DB::raw('postDate ASC NULLS LAST');
                    }
                    if (DB::isMysql()) {
                        return DB::raw('postDate IS NULL DESC, postDate DESC');
                    }

                    return DB::raw('postDate DESC NULLS FIRST');
                },
                'attribute' => 'postDate',
                'defaultDir' => 'desc',
            ],
            [
                'label' => t('Expiry Date'),
                'orderBy' => 'expiryDate',
                'defaultDir' => 'desc',
            ],
            [
                'label' => t('Date Created'),
                'orderBy' => 'dateCreated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => t('Date Updated'),
                'orderBy' => 'dateUpdated',
                'defaultDir' => 'desc',
            ],
        ];
    }

    #[Override]
    protected static function defineTableAttributes(): array
    {
        $attributes = array_merge(parent::defineTableAttributes(), [
            'section' => ['label' => t('Section')],
            'type' => ['label' => t('Entry Type')],
            'authors' => ['label' => t('Authors')],
            'ancestors' => ['label' => t('Ancestors')],
            'parent' => ['label' => t('Parent')],
            'postDate' => ['label' => t('Post Date')],
            'expiryDate' => ['label' => t('Expiry Date')],
            'revisionNotes' => ['label' => t('Revision Notes')],
            'revisionCreator' => ['label' => t('Last Edited By')],
            'drafts' => ['label' => t('Drafts')],
        ]);

        // Hide Author & Last Edited By from Craft Solo
        if (Edition::get() === Edition::Solo) {
            unset($attributes['authors'], $attributes['revisionCreator']);
        }

        return $attributes;
    }

    #[Override]
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

    #[Override]
    protected static function defineCardAttributes(): array
    {
        $currentUser = Auth::user();

        $attributes = array_merge(parent::defineCardAttributes(), [
            'section' => [
                'label' => t('Section'),
                'placeholder' => fn () => t('Section'),
            ],
            'type' => [
                'label' => t('Entry Type'),
                'placeholder' => fn () => t('Entry Type'),
            ],
            'authors' => [
                'label' => t('Authors'),
                'placeholder' => fn () => $currentUser ? Cp::elementChipHtml($currentUser) : '',
            ],
            'parent' => [
                'label' => t('Parent'),
                'placeholder' => fn () => Html::tag(
                    'span',
                    t('Parent {type} Title', ['type' => self::displayName()]),
                    ['class' => 'card-placeholder'],
                ),
            ],
            'postDate' => [
                'label' => t('Post Date'),
                'placeholder' => fn () => (new DateTime)->sub(new DateInterval('P15D')),
            ],
            'expiryDate' => [
                'label' => t('Expiry Date'),
                'placeholder' => fn () => (new DateTime)->add(new DateInterval('P15D')),
            ],
            'revisionNotes' => [
                'label' => t('Revision Notes'),
                'placeholder' => fn () => t('Revision Notes'),
            ],
            'revisionCreator' => [
                'label' => t('Last Edited By'),
                'placeholder' => fn () => $currentUser ? Cp::elementChipHtml($currentUser) : '',
            ],
            'drafts' => [
                'label' => t('Drafts'),
                'placeholder' => fn () => Html::tag(
                    'span',
                    t('Draft {num}', ['num' => 1]),
                    ['class' => 'card-placeholder'],
                ),
            ],
        ]);

        // Hide Author & Last Edited By from Craft Solo
        if (Edition::get() === Edition::Solo) {
            unset($attributes['authors'], $attributes['revisionCreator']);
        }

        return $attributes;
    }

    #[Override]
    public static function attributePreviewHtml(array $attribute): mixed
    {
        return match ($attribute['value']) {
            'authors', 'parent', 'revisionCreator', 'drafts' => $attribute['placeholder'],
            default => parent::attributePreviewHtml($attribute),
        };
    }

    #[Override]
    public static function eagerLoadingMap(array $sourceElements, string $handle): array|null|false
    {
        switch ($handle) {
            case 'author':
            case 'authors':
                $entryIds = array_map(fn (ElementInterface $entry) => $entry->id, $sourceElements);

                $map = DB::table(Table::ENTRIES_AUTHORS)
                    ->select([
                        'source' => 'entryId',
                        'target' => 'authorId',
                    ])
                    ->whereIn('entryId', $entryIds)
                    ->orderBy('sortOrder')
                    ->get()
                    ->map(fn (object $row) => (array) $row)
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

    #[Override]
    public static function baseGqlType(): Type
    {
        return EntryInterface::getType();
    }

    #[Override]
    public static function gqlScopesByContext(mixed $context): array
    {
        /** @var \CraftCms\Cms\Section\Data\Section $section */
        $section = $context['section'];

        return [
            "sections.$section->uid",
        ];
    }

    #[Override]
    protected static function prepElementQueryForTableAttribute(ElementQueryInterface $elementQuery, string $attribute): void
    {
        match ($attribute) {
            'authors' => $elementQuery->andWith(['authors', ['status' => null]]),
            default => parent::prepElementQueryForTableAttribute($elementQuery, $attribute),
        };
    }

    /**
     * @var int|null Section ID
     *               ---
     *               ```php
     *               echo $entry->sectionId;
     *               ```
     *               ```twig
     *               {{ entry.sectionId }}
     *               ```
     */
    public ?int $sectionId = null;

    /**
     * @var bool Collapsed
     *
     * @since 5.0.0
     */
    public bool $collapsed = false;

    /**
     * @var DateTime|null Post date
     *                    ---
     *                    ```php
     *                    echo Craft::$app->formatter->asDate($entry->postDate, 'short');
     *                    ```
     *                    ```twig
     *                    {{ entry.postDate|date('short') }}
     *                    ```
     */
    #[AllowedInSandbox]
    public ?DateTime $postDate = null;

    /**
     * @var DateTime|null Expiry date
     *                    ---
     *                    ```php
     *                    if ($entry->expiryDate) {
     *                    echo Craft::$app->formatter->asDate($entry->expiryDate, 'short');
     *                    }
     *                    ```
     *                    ```twig
     *                    {% if entry.expiryDate %}
     *                    {{ entry.expiryDate|date('short') }}
     *                    {% endif %}
     *                    ```
     */
    #[AllowedInSandbox]
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
     *
     * @see beforeDelete()
     *
     * @internal
     */
    public bool $deletedWithEntryType = false;

    /**
     * @var bool Whether the entry was deleted along with its section
     *
     * @see beforeDelete()
     *
     * @internal
     */
    public bool $deletedWithSection = false;

    /**
     * @var bool Whether to force-place the entry within its structure.
     *
     * @since 5.7.0
     */
    public bool $placeInStructure = false;

    /**
     * @var int[] Entry author IDs
     *
     * @see getAuthorIds()
     * @see setAuthorIds()
     */
    private array $_authorIds;

    /**
     * @var int[] Original entry author IDs
     *
     * @see getOldAuthorIds()
     * @see setAuthorIds()
     */
    private ?array $_oldAuthorIds = null;

    /**
     * @var User[]|null Entry authors
     *
     * @see getAuthors()
     * @see setAuthors()
     */
    private ?array $_authors = null;

    /**
     * @var int|null Type ID
     *
     * @see getType()
     */
    private ?int $_typeId = null;

    private ?int $_oldTypeId = null;

    /**
     * @var EntryType|null Entry Type
     *
     * @see getType()
     */
    private ?EntryType $_type = null;

    /**
     * @since 3.5.0
     */
    #[Override]
    public function init(): void
    {
        parent::init();
        if (isset($this->id)) {
            $this->oldStatus = $this->getStatus();
        }
        $this->_oldTypeId = $this->_typeId;
    }

    #[Override]
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

    #[Override]
    public function extraFields(): array
    {
        $names = $this->traitExtraFields();
        $names[] = 'author';
        $names[] = 'authors';
        $names[] = 'section';
        $names[] = 'type';

        return $names;
    }

    #[Override]
    public function attributeLabels(): array
    {
        $authorLabel = t('{max, plural, =1{Author} other {Authors}}', [
            'max' => $this->getSection()->maxAuthors ?? PHP_INT_MAX,
        ]);

        return array_merge(parent::attributeLabels(), [
            'authorId' => $authorLabel,
            'authorIds' => $authorLabel,
            'expiryDate' => t('Expiry Date'),
            'postDate' => t('Post Date'),
            'typeId' => t('Entry Type'),
        ]);
    }

    #[Override]
    public function setAttributesFromRequest(array $values): void
    {
        $authorIds = Arr::pull($values, 'authorIds');
        $authorId = Arr::pull($values, 'authorId');

        parent::setAttributesFromRequest($values);

        // Only set the author if the user has permission to change it
        if (
            ($authorIds !== null || $authorId !== null) &&
            isset($this->sectionId)
        ) {
            $authorIds = $this->normalizeAuthorIds($authorIds ?? $authorId);
            $oldAuthorIds = $this->getAuthorIds();
            if (
                $authorIds !== $oldAuthorIds &&
                $this->canChangeAuthor()
            ) {
                $this->_oldAuthorIds = $oldAuthorIds;
                $this->setAuthorIds($authorIds);
            }
        }

        // Did the entry type just change?
        if (isset($this->_typeId, $this->_oldTypeId) && $this->_typeId !== $this->_oldTypeId) {
            $this->handleChangedTypeId();
        }
    }

    #[Override]
    public function shouldValidateTitle(): bool
    {
        $entryType = $this->getType();
        if (! $entryType->hasTitleField) {
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

    public function getColor(): ?Color
    {
        return $this->getType()->getColor();
    }

    #[Override]
    public function getSupportedSites(): array
    {
        if (isset($this->fieldId)) {
            return $this->getField()->getSupportedSitesForElement($this);
        }

        if (! isset($this->sectionId)) {
            throw new InvalidConfigException('Either `sectionId` or `fieldId` + `ownerId` must be set on the entry.');
        }

        $section = $this->getSection();
        /** @var Site[] $allSites */
        $allSites = Sites::getAllSites(true)->keyBy('id')->all();
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
                    ->drafts(null)
                    ->provisionalDrafts(null)
                    ->revisions($this->getIsRevision())
                    ->pluck('elements_sites.siteId')
                    ->all();
            } else {
                $currentSites = [];
            }

            // If this is being duplicated from another element (e.g. a draft), include any sites the source element is saved to as well
            if (! empty($this->duplicateOf->id)) {
                array_push($currentSites, ...self::find()
                    ->status(null)
                    ->id($this->duplicateOf->id)
                    ->site('*')
                    ->drafts(null)
                    ->provisionalDrafts(null)
                    ->revisions($this->duplicateOf->getIsRevision())
                    ->pluck('elements_sites.siteId')
                    ->all()
                );
            }

            $currentSites = array_flip($currentSites);
        }

        foreach ($section->getSiteSettings() as $siteSettings) {
            switch ($section->propagationMethod) {
                case PropagationMethod::None:
                    $include = $siteSettings->siteId === $this->siteId;
                    $propagate = true;
                    break;
                case PropagationMethod::SiteGroup:
                    $include = $allSites[$siteSettings->siteId]->groupId === $allSites[$this->siteId]->groupId;
                    $propagate = true;
                    break;
                case PropagationMethod::Language:
                    $include = $allSites[$siteSettings->siteId]->getLanguage() === $allSites[$this->siteId]->getLanguage();
                    $propagate = true;
                    break;
                case PropagationMethod::Custom:
                    $include = true;
                    // Only actually propagate to this site if it's the current site, or the entry has been assigned
                    // a status for this site, or the entry already exists for this site
                    $propagate = (
                        $siteSettings->siteId === $this->siteId ||
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
     * @since 3.5.0
     */
    #[Override]
    protected function cacheTags(): array
    {
        $tags = [
            sprintf('entryType:%s', $this->getType()->id),
        ];

        // Did the entry type just change?
        if ($this->getType()->id !== $this->_oldTypeId) {
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
     * @throws InvalidConfigException if [[siteId]] is not set to a site ID that the entry’s section is enabled for
     */
    public function getUriFormat(): ?string
    {
        if (isset($this->fieldId)) {
            return $this->getField()->getUriFormatForElement($this);
        }

        if (! isset($this->sectionId)) {
            throw new InvalidConfigException('Either `sectionId` or `fieldId` + `ownerId` must be set on the entry.');
        }

        $sectionSiteSettings = $this->getSection()->getSiteSettings();

        if (! isset($sectionSiteSettings[$this->siteId])) {
            throw new InvalidConfigException('Entry’s section ('.$this->sectionId.') is not enabled for site '.$this->siteId);
        }

        return $sectionSiteSettings[$this->siteId]->uriFormat;
    }

    protected function route(): ?array
    {
        // Make sure that the entry is actually live
        if (! $this->previewing && $this->getStatus() != self::STATUS_LIVE) {
            return null;
        }

        $section = $this->getSection();

        if (! $section) {
            return null;
        }

        // Make sure the section is set to have URLs for this site
        $sectionSiteSettings = $section->getSiteSettings()[$this->siteId] ?? null;

        if (! $sectionSiteSettings?->hasUrls) {
            return null;
        }

        return [
            'templates/render', [
                'template' => (string) $sectionSiteSettings->template,
                'variables' => [
                    'entry' => $this,
                ],
            ],
        ];
    }

    #[Override]
    protected function crumbs(): array
    {
        $section = $this->getSection();

        if (! $section) {
            return [];
        }

        $page = $section->getPage();
        $crumbs = [
            [
                'label' => $page && $page !== 'Entries' ? t($page, category: 'site') : t('Entries'),
                'url' => sprintf('content/%s', $page ? Str::slug($page) : 'entries'),
            ],
        ];

        // Is the section’s source enabled?
        $elementSourcesService = app(ElementSources::class);
        $sourceKey = $section->type === SectionType::Single ? 'singles' : "section:$section->uid";
        if ($elementSourcesService->sourceExists(Entry::class, $sourceKey)) {
            $sections = Sections::getEditableSections();

            // Filter out any sections that aren’t enabled for this site
            $requestedSite = Cp::requestedSite();
            if ($requestedSite) {
                $sections = $sections->filter(fn (Section $s) => in_array($requestedSite->id, $s->getSiteIds()));
            }

            // Filter out any sections that don’t have an enabled source / don’t belong in this page
            $sources = $elementSourcesService->getSources(Entry::class, page: $page)->all();
            $sourceKeys = array_flip(array_filter(array_map(fn (array $source) => $source['key'] ?? null, $sources)));
            $sections = $sections->filter(function (Section $s) use ($sourceKeys) {
                $key = $s->type === SectionType::Single ? 'singles' : "section:$s->uid";

                return isset($sourceKeys[$key]);
            });

            /** @var Collection $sectionOptions */
            $sectionOptions = $sections
                ->filter(fn (Section $s) => $s->type !== SectionType::Single)
                ->map(fn (Section $s) => [
                    'label' => $s->getUiLabel(),
                    'url' => $s->getCpIndexUri(),
                    'selected' => $s->id === $section->id,
                ]);

            /** @var Section|null $firstSingle */
            $firstSingle = $sections->first(fn (Section $s) => $s->type === SectionType::Single);
            if ($firstSingle) {
                $sectionOptions->prepend([
                    'label' => t('Singles'),
                    'url' => $firstSingle->getCpIndexUri(),
                    'selected' => $section->type === SectionType::Single,
                ]);
            }

            if ($sectionOptions->count() > 1) {
                $crumbs[] = [
                    'menu' => [
                        'label' => t('Select section'),
                        'items' => $sectionOptions->all(),
                    ],
                ];
            } else {
                $crumbs[] = $sectionOptions->first();
            }
        } elseif ($section->type !== SectionType::Single) {
            // Just show its name w/o a link
            $crumbs[] = [
                'label' => $section->getUiLabel(),
            ];
        }

        if ($section->type === SectionType::Structure) {
            $elementsService = Craft::$app->getElements();
            $user = Auth::user();

            $ancestors = $this->getAncestors();
            if ($ancestors instanceof ElementQueryInterface) {
                $ancestors->status(null);
            }

            foreach ($ancestors->all() as $ancestor) {
                if ($elementsService->canView($ancestor, $user)) {
                    $crumbs[] = [
                        'html' => Cp::elementChipHtml($ancestor, [
                            'class' => 'chromeless',
                            'hyperlink' => true,
                        ]),
                    ];
                }
            }
        }

        return $crumbs;
    }

    #[Override]
    public function getUiLabel(): string
    {
        if ($this->fieldId) {
            $entryType = $this->getType();
            if (! $entryType->hasTitleField && ! $entryType->titleFormat && $entryType->uiLabelFormat === '{title}') {
                return '';
            }
        }

        return parent::getUiLabel();
    }

    protected function uiLabel(): ?string
    {
        if ($this->getType()->uiLabelFormat !== '{title}') {
            $uiLabel = Craft::$app->getView()->renderObjectTemplate($this->getType()->uiLabelFormat, $this);
            if ($uiLabel !== '') {
                return $uiLabel;
            }
        }

        if (! $this->fieldId && (! isset($this->title) || trim($this->title) === '')) {
            $section = $this->getSection();
            if ($section?->type === SectionType::Single) {
                return $section->getUiLabel();
            }

            return t('Untitled {type}', [
                'type' => self::lowerDisplayName(),
            ]);
        }

        return null;
    }

    #[Override]
    public function getChipLabelHtml(): string
    {
        $html = parent::getChipLabelHtml();
        if ($html !== '') {
            return $html;
        }

        return Html::tag('em', t($this->getType()->name, category: 'site'), [
            'class' => 'light',
        ]);
    }

    #[Override]
    public function showStatusIndicator(): bool
    {
        return $this->getType()->showStatusField;
    }

    public function getCardTitle(): string
    {
        return $this->getType()->getUiLabel();
    }

    #[Override]
    protected function previewTargets(): array
    {
        if ($this->fieldId) {
            return parent::previewTargets();
        }

        return array_map(function ($previewTarget) {
            $previewTarget['label'] = t($previewTarget['label'], category: 'site');

            return $previewTarget;
        }, $this->getSection()->previewTargets ?? []);
    }

    public function getIcon(): ?string
    {
        return $this->getType()->getIcon();
    }

    /**
     * Returns the reference string to this element.
     */
    public function getRef(): ?string
    {
        if (isset($this->fieldId)) {
            return null;
        }

        return $this->getSection()->handle.'/'.$this->slug;
    }

    #[Override]
    public function getIsTitleTranslatable(): bool
    {
        return $this->getType()->titleTranslationMethod !== TranslationMethod::None;
    }

    #[Override]
    public function getTitleTranslationDescription(): ?string
    {
        return ElementHelper::translationDescription($this->getType()->titleTranslationMethod);
    }

    #[Override]
    public function getTitleTranslationKey(): string
    {
        $type = $this->getType();

        return ElementHelper::translationKey($this, $type->titleTranslationMethod, $type->titleTranslationKeyFormat);
    }

    #[Override]
    public function getIsSlugTranslatable(): bool
    {
        return $this->getType()->slugTranslationMethod !== TranslationMethod::None;
    }

    #[Override]
    public function getSlugTranslationDescription(): ?string
    {
        return ElementHelper::translationDescription($this->getType()->slugTranslationMethod);
    }

    #[Override]
    public function getSlugTranslationKey(): string
    {
        $type = $this->getType();

        return ElementHelper::translationKey($this, $type->slugTranslationMethod, $type->slugTranslationKeyFormat);
    }

    #[Override]
    public function getFieldLayout(): ?FieldLayout
    {
        try {
            return $this->getType()->getFieldLayout();
        } catch (InvalidConfigException) {
            // The entry type was probably deleted
            return null;
        }
    }

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
     * @throws InvalidConfigException if [[sectionId]] is missing or invalid
     */
    public function getSection(): ?Section
    {
        if (! isset($this->sectionId)) {
            return null;
        }

        $section = Sections::getSectionById($this->sectionId);
        if (! $section) {
            throw new InvalidConfigException("Invalid section ID: $this->sectionId");
        }

        return $section;
    }

    /**
     * Returns the entry type ID.
     *
     * @since 4.0.0
     */
    public function getTypeId(): int
    {
        return $this->getType()->id;
    }

    /**
     * Sets the entry type ID.
     *
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
     * @return EntryType[]
     *
     * @throws InvalidConfigException
     *
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

        if ($triggerEvent) {
            event($event = new DefineEntryTypes($this, $entryTypes));

            return $event->entryTypes;
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
     * @throws InvalidConfigException if [[typeId]] is invalid, or the section has no entry types
     */
    public function getType(): EntryType
    {
        if (! isset($this->_type)) {
            if (isset($this->_typeId)) {
                $entryType = Arr::first(
                    $this->getAvailableEntryTypes(false),
                    fn (EntryType $entryType) => $entryType->id === $this->_typeId,
                );
                if (! $entryType) {
                    // Maybe the section/field no longer allows this type,
                    // so get it directly from the Entries service instead
                    $entryType = EntryTypes::getEntryTypeById($this->_typeId, true);
                    if (! $entryType) {
                        throw new InvalidConfigException("Invalid entry type ID: $this->_typeId");
                    }
                }
            } else {
                // Default to the section/field's first entry type
                $entryType = Arr::first($this->getAvailableEntryTypes());
                if (! $entryType) {
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
     * @since 4.0.0
     */
    #[AllowedInSandbox]
    public function getAuthorId(): ?int
    {
        return $this->getAuthorIds()[0] ?? null;
    }

    /**
     * Sets the entry author’s ID.
     *
     * @param  int|array{0:int}|string|null  $authorId
     *
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
     *
     * @since 5.0.0
     */
    #[AllowedInSandbox]
    public function getAuthorIds(): array
    {
        if (! isset($this->_authorIds)) {
            $this->_authorIds = array_map(fn (User $author) => $author->id, $this->getAuthors());
        }

        return $this->_authorIds;
    }

    public function getOldAuthorIds(): ?array
    {
        return $this->_oldAuthorIds;
    }

    /**
     * Sets the entry authors’ IDs.
     *
     * @param  User[]|int[]|string|int|null  $authorIds
     *
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
        $authorIds = Arr::wrap($authorIds);

        return array_map(fn ($id) => (int) $id, $authorIds);
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
     * @throws InvalidConfigException if [[authorId]] is set but invalid
     */
    #[AllowedInSandbox]
    public function getAuthor(): ?User
    {
        return $this->getAuthors()[0] ?? null;
    }

    /**
     * Sets the entry author.
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
     *
     * @since 5.0.0
     */
    #[AllowedInSandbox]
    public function getAuthors(): array
    {
        if (! isset($this->_authors)) {
            if (! isset($this->sectionId)) {
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
                    ->join(
                        new Alias(Table::ENTRIES_AUTHORS, 'entries_authors'),
                        function (JoinClause $join) {
                            $join->on('entries_authors.authorId', '=', 'users.id')
                                ->where('entries_authors.entryId', '=', $this->id);
                        }
                    )
                    ->orderBy('entries_authors.sortOrder')
                    ->all();
            }

            $this->setAuthors($authors);
        }

        return $this->_authors;
    }

    /**
     * Sets the entry authors.
     *
     * @param  User[]  $authors
     *
     * @since 5.0.0
     */
    public function setAuthors(array $authors): void
    {
        $this->_authors = $authors;
        $this->_authorIds = array_map(fn (User $author) => $author->id, $authors);
    }

    #[Override]
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
            ! $this->postDate || $this->postDate > $now => self::STATUS_PENDING,
            $this->expiryDate && $this->expiryDate <= $now => self::STATUS_EXPIRED,
            default => self::STATUS_LIVE,
        };
    }

    /**
     * Sets the status, if it’s stored statically.
     *
     * @param  self::STATUS_LIVE|self::STATUS_PENDING|self::STATUS_EXPIRED  $status
     *
     * @since 5.7.0
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function createAnother(): self
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
            /** @var \CraftCms\Cms\Section\Data\SectionSiteSettings $siteSettings */
            $siteSettings = Collection::make($section->getSiteSettings())->firstWhere('siteId', $this->siteId);
            $enabled = $siteSettings->enabledByDefault;
        } else {
            $enabled = true;
        }

        if (Sites::isMultiSite() && count($entry->getSupportedSites()) > 1) {
            $entry->enabled = true;
            $entry->setEnabledForSite($enabled);
        } else {
            $entry->enabled = $enabled;
            $entry->setEnabledForSite(true);
        }

        // Structure parent
        if ($section?->type === SectionType::Structure && $section->maxLevels !== 1) {
            $entry->setParentId($this->getParentId());
        }

        return $entry;
    }

    public function hasRevisions(): bool
    {
        $section = $this->getSection();
        if ($section) {
            return $section->enableVersioning;
        }

        $field = $this->getField();

        return $field instanceof Matrix && $field->enableVersioning;
    }

    protected function cpEditUrl(): string
    {
        $section = $this->getSection();

        if (! $section) {
            // use the generic element editor URL
            return ElementHelper::elementEditorUrl($this, false);
        }

        $path = sprintf('%s/%s', $section->getCpIndexUri(), $this->getCanonicalId());

        // Ignore homepage/temp slugs
        if ($this->slug && ! str_starts_with($this->slug, '__')) {
            $path .= sprintf('-%s', str_replace('/', '-', $this->slug));
        }

        return $path;
    }

    public function getPostEditUrl(): string
    {
        return UrlHelper::cpUrl('entries');
    }

    protected function cpRevisionsUrl(): string
    {
        if (! $this->sectionId) {
            return ElementHelper::elementRevisionsUrl($this);
        }

        return sprintf('%s/revisions', $this->cpEditUrl());
    }

    #[Override]
    protected function safeActionMenuItems(): array
    {
        $actions = parent::safeActionMenuItems();

        if (
            Auth::user()?->isAdmin() &&
            Cms::config()->allowAdminChanges
        ) {
            // Entry type settings
            $entryTypeEditId = sprintf('edit-entry-type-%s', mt_rand());
            $actions[] = [
                'id' => $entryTypeEditId,
                'icon' => 'gear',
                'label' => t('Entry type settings'),
            ];

            AssetRegistry::jsWithVars(fn ($id, $params, $isNestedEntry) => <<<JS
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
                InputNamespace::namespaceInputId($entryTypeEditId),
                ['entryTypeId' => $this->typeId],
                isset($this->fieldId),
            ]);

            // Section settings
            if (! empty($this->sectionId)) {
                $sectionEditId = sprintf('edit-section-%s', mt_rand());
                $actions[] = [
                    'id' => $sectionEditId,
                    'icon' => 'gear',
                    'label' => t('Section settings'),
                ];

                AssetRegistry::jsWithVars(fn ($id, $params) => <<<JS
    (() => {
      $('#' + $id).on('activate', function() {
        new Craft.CpScreenSlideout('sections/edit-section', {params: $params})
      });
    })();
    JS, [
                    InputNamespace::namespaceInputId($sectionEditId),
                    ['sectionId' => $this->sectionId],
                ]);
            }

            // Field settings
            if (
                ! empty($this->fieldId) &&
                Craft::$app->controller instanceof ElementsController &&
                Craft::$app->controller->element === $this
            ) {
                $fieldEditId = sprintf('edit-field-%s', mt_rand());
                $actions[] = [
                    'id' => $fieldEditId,
                    'icon' => 'gear',
                    'label' => Craft::t('app', 'Field settings'),
                ];

                AssetRegistry::jsWithVars(fn ($id, $params) => <<<JS
    (() => {
      $('#' + $id).on('activate', function() {
        new Craft.CpScreenSlideout('fields/edit-field', {params: $params})
      });
    })();
    JS, [
                    InputNamespace::namespaceInputId($fieldEditId),
                    ['fieldId' => $this->fieldId],
                ]);
            }
        }

        return $actions;
    }

    /**
     * @since 3.3.0
     */
    #[Override]
    public function getGqlTypeName(): string
    {
        return self::gqlTypeName($this->getType());
    }

    /**
     * @param  User[]  $elements
     */
    #[Override]
    public function setEagerLoadedElements(string $handle, array $elements, EagerLoadPlan $plan): void
    {
        match ($plan->handle) {
            'author', 'authors' => $this->setAuthors($elements),
            default => $this->traitSetEagerLoadedElements($handle, $elements, $plan),
        };
    }

    // Indexes, etc.
    // -------------------------------------------------------------------------

    #[Override]
    protected function attributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'authors':
                $authors = $this->getAuthors();
                $html = '';
                foreach ($authors as $author) {
                    $html .= Cp::elementChipHtml($author);
                }

                return $html;
            case 'section':
                $section = $this->getSection();
                if (! $section) {
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
                    return t('Unknown');
                }
            default:
                return parent::attributeHtml($attribute);
        }
    }

    #[Override]
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
                    'label' => t('{max, plural, =1{Author} other {Authors}}', [
                        'max' => $section->maxAuthors ?? PHP_INT_MAX,
                    ]),
                    'id' => 'authorIds',
                    'name' => 'authorIds',
                    'elementType' => User::class,
                    'selectionLabel' => t('Choose'),
                    'criteria' => [
                        'can' => "viewEntries:$section->uid",
                    ],
                    'single' => false,
                    'elements' => $authors ?: null,
                    'disabled' => false,
                    'errors' => $this->errors()->get('authorIds'),
                    'limit' => $section->maxAuthors,
                ]);
            default:
                return parent::inlineAttributeInputHtml($attribute);
        }
    }

    #[Override]
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
        if (! $user) {
            $user = Auth::user();
            if (! $user) {
                return false;
            }
        }

        $section = $this->getSection();

        if (! $section) {
            return false;
        }

        // disallow moving singles and trashed entries
        if ($section->type === SectionType::Single || $this->trashed) {
            return false;
        }

        // if there aren't any compatible sections, just don't bother with further checks
        if (! $this->_moveCompatibleSectionsCount()) {
            return false;
        }

        if ($this->getIsDraft()) {
            return
                $this->draftCreatorId === $user->id ||
                $user->can("savePeerEntryDrafts:$section->uid");
        }

        if (! $user->can("saveEntries:$section->uid")) {
            return false;
        }

        return
            in_array($user->id, $this->getAuthorIds(), true) ||
            $user->can("savePeerEntries:$section->uid");
    }

    /**
     * Get sections that this entry could be moved to - sections that use the exact same entry type.
     */
    private function _moveCompatibleSectionsCount(): int
    {
        // get entry type id
        $entryTypeId = $this->getType()->id;

        // get sections all editable sections without singles and without the section this entry belongs to
        // get all entry types for them
        $sections = Sections::getEditableSections()
            ->filter(fn (Section $s) => $s->type !== SectionType::Single && $s->id !== $this->sectionId)
            ->map(fn (Section $s) => [
                'entryTypes' => $s->getEntryTypes(),
            ]);

        // get sections that use the same entry type as this entry
        $compatibleSections = $sections
            ->filter(fn (array $s) => collect($s['entryTypes'])->contains('id', $entryTypeId));

        return $compatibleSections->count();
    }

    #[Override]
    public function metaFieldsHtml(bool $static): string
    {
        $fields = [];
        $view = Craft::$app->getView();
        $section = $this->getSection();
        $user = Auth::user();

        $this->_applyActionBtnEntryTypeCompatibility();

        // Type
        $fields['type'] = (function () use ($static) {
            $entryTypes = $this->getAvailableEntryTypes();
            if (Collection::make($entryTypes)->doesntContain(fn (EntryType $entryType) => $entryType->id === $this->typeId)) {
                $entryTypes[] = $this->getType();
            }
            if (count($entryTypes) <= 1 && $this->isEntryTypeAllowed($entryTypes)) {
                return null;
            }

            return Cp::customSelectFieldHtml([
                'fieldClass' => 'entry-type-select',
                'status' => $this->getAttributeStatus('typeId'),
                'label' => t('Entry Type'),
                'id' => 'entryType',
                'name' => 'typeId',
                'value' => $this->getType()->id,
                'options' => array_map(fn (EntryType $et) => [
                    'icon' => $et->icon,
                    'iconColor' => $et->color,
                    'label' => t($et->name, category: 'site'),
                    'value' => $et->id,
                ], $entryTypes),
                'disabled' => $static,
                'attribute' => 'typeId',
                'errors' => $this->errors()->get('typeId'),
            ]);
        })();

        // Slug
        if ($this->getType()->showSlugField) {
            $fields['slug'] = $this->slugFieldHtml($static);
        }

        // Parent
        if ($section?->type === SectionType::Structure && $section->maxLevels !== 1) {
            $fields['parent'] = (function () use ($static, $section) {
                if ($parentId = $this->getParentId()) {
                    $parent = Entries::getEntryById($parentId, $this->siteId, [
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
                    'label' => t('Parent'),
                    'id' => 'parentId',
                    'name' => 'parentId',
                    'elementType' => self::class,
                    'selectionLabel' => t('Choose'),
                    'sources' => ["section:$section->uid"],
                    'criteria' => $this->_parentOptionCriteria($section),
                    'limit' => 1,
                    'elements' => $parent ? [$parent] : [],
                    'disabled' => $static,
                    'describedBy' => 'parentId-label',
                    'errors' => $this->errors()->get('parentId'),
                ]);
            })();
        }

        if ($section && $section->type !== SectionType::Single) {
            // Author
            if (
                $section->maxAuthors !== 0 &&
                Edition::get() !== Edition::Solo
            ) {
                $fields['authors'] = (function () use ($static, $section, $user) {
                    $authors = $this->getAuthors();

                    return Cp::elementSelectFieldHtml([
                        'status' => $this->getAttributeStatus('authorIds'),
                        'label' => t('{max, plural, =1{Author} other {Authors}}', [
                            'max' => $section->maxAuthors ?? PHP_INT_MAX,
                        ]),
                        'id' => 'authorIds',
                        'name' => 'authorIds',
                        'elementType' => User::class,
                        'selectionLabel' => t('Choose'),
                        'criteria' => [
                            'can' => "viewEntries:$section->uid",
                        ],
                        'single' => false,
                        'elements' => $authors ?: null,
                        'disabled' => $static || ! $this->canChangeAuthor($user),
                        'errors' => $this->errors()->get('authorIds'),
                        'limit' => $section->maxAuthors,
                    ]);
                })();
            }

            $isDeltaRegistrationActive = $view->getIsDeltaRegistrationActive();
            $view->setIsDeltaRegistrationActive(true);
            $view->registerDeltaName('postDate');
            $view->registerDeltaName('expiryDate');
            $view->setIsDeltaRegistrationActive($isDeltaRegistrationActive);

            // Post Date
            $fields['postDate'] = Cp::dateTimeFieldHtml([
                'status' => $this->getAttributeStatus('postDate'),
                'label' => t('Post Date'),
                'id' => 'postDate',
                'name' => 'postDate',
                'value' => $this->_userPostDate(),
                'errors' => $this->errors()->get('postDate'),
                'disabled' => $static,
            ]);

            // Expiry Date
            $fields['expiryDate'] = Cp::dateTimeFieldHtml([
                'status' => $this->getAttributeStatus('expiryDate'),
                'label' => t('Expiry Date'),
                'id' => 'expiryDate',
                'name' => 'expiryDate',
                'value' => $this->expiryDate,
                'errors' => $this->errors()->get('expiryDate'),
                'disabled' => $static,
            ]);
        }

        $fields[] = parent::metaFieldsHtml($static);

        event($event = new DefineMetaFields($this, $static, $fields));

        return implode("\n", $event->fields);
    }

    /**
     * Checks if the "Apply Draft" and "Revert to a revision" buttons should be disabled and if so
     * applies the tooltip message.
     *
     * @throws InvalidConfigException
     */
    private function _applyActionBtnEntryTypeCompatibility(): void
    {
        $draftMessage = t('This draft’s entry type is no longer available. You can still view it, but not apply it.');
        $revisionMessage = t('This revision’s entry type is no longer available. You can still view it, but not revert to it.');

        if (! $this->isEntryTypeCompatible()) {
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
            AssetRegistry::js($js);
        }
    }

    /**
     * Returns whether the current user has permission to change this entry’s author.
     */
    private function canChangeAuthor(?User $user = null): bool
    {
        if (! $user && ! $user = Auth::user()) {
            return false;
        }

        $section = $this->getSection();

        if (! $user->can("viewPeerEntries:$section->uid")) {
            return false;
        }

        $authorIds = $this->getAuthorIds();

        return
            empty($authorIds) ||
            in_array($user->id, $authorIds) ||
            $user->can("changeAuthorForPeerEntries:$section->uid");
    }

    #[Override]
    public function showStatusField(): bool
    {
        try {
            $showStatusField = $this->getType()->showStatusField;
        } catch (InvalidConfigException) {
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
                    ->value('level');
                $depth = 1 + ($maxDepth ?: $this->level) - $this->level;
            } else {
                $depth = 1;
            }

            $parentOptionCriteria['level'] = sprintf('<=%s', $section->maxLevels - $depth);
        }

        event($event = new DefineParentSelectionCriteria($this, $parentOptionCriteria));

        return $event->criteria;
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

        if (! $entryType->titleFormat) {
            $this->title = null;

            return;
        }

        // Make sure that the locale has been loaded in case the title format has any Date/Time fields
        // Set Craft to the entry’s site’s language, in case the title format has any static translations
        $language = app()->getLocale();
        $locale = I18N::getLocale();
        $formattingLocale = I18N::getFormattingLocale();
        $site = $this->getSite();
        $tempLocale = I18N::getLocaleById($site->getLanguage());
        app()->setLocale($site->getLanguage());
        Craft::$app->set('locale', $tempLocale);
        Craft::$app->set('formattingLocale', $tempLocale);
        $title = Craft::$app->getView()->renderObjectTemplate($entryType->titleFormat, $this);
        if ($title !== '') {
            $this->title = $title;
        }
        app()->setLocale($language);
        Craft::$app->set('locale', $locale);
        Craft::$app->set('formattingLocale', $formattingLocale);
    }

    /**
     * Returns the Post Date value that should be shown on the edit form.
     */
    private function _userPostDate(): ?DateTime
    {
        if (! $this->postDate || ($this->getIsUnpublishedDraft() && $this->postDate == $this->dateCreated)) {
            // Pretend the post date hasn't been set yet, even if it has
            return null;
        }

        return $this->postDate;
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @throws Exception if reasons
     */
    #[Override]
    public function beforeSave(bool $isNew): bool
    {
        if ($this->_shouldSaveRevision()) {
            // Make sure the entry has at least one revision
            $hasRevisions = self::find()
                ->revisionOf($this)
                ->site('*')
                ->status(null)
                ->exists();

            if (! $hasRevisions) {
                /** @var self|null $current */
                $current = self::find()
                    ->id($this->id)
                    ->site('*')
                    ->status(null)
                    ->one();

                // May be null if the entry is currently stored as an unpublished draft
                if ($current) {
                    app(Revisions::class)->createRevision(
                        $current,
                        $current->getAuthorId(),
                        sprintf('Revision from %s', I18N::getFormatter()->asDatetime($current->dateUpdated)),
                    );
                }
            }
        }

        $section = $this->getSection();
        if ($section) {
            // Set the structure ID for Element::attributes() and afterSave()
            if ($section->type === SectionType::Structure) {
                $this->structureId = $section->structureId;

                // Has the entry been assigned to a new parent?
                if (! $this->duplicateOf && $this->hasNewParent()) {
                    if ($parentId = $this->getParentId()) {
                        $parentEntry = Entries::getEntryById($parentId, '*', [
                            'preferSites' => [$this->siteId],
                            'drafts' => null,
                            'draftOf' => false,
                        ]);

                        if (! $parentEntry) {
                            throw new InvalidConfigException("Invalid parent ID: $parentId");
                        }
                    } else {
                        $parentEntry = null;
                    }

                    $this->setParent($parentEntry);
                }
            }

            // Section type-specific stuff
            if ($section->type === SectionType::Single) {
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
     */
    private function maybeSetDefaultAttributes(): void
    {
        // if we're resaving, we shouldn't be setting the defaults
        if ($this->resaving) {
            return;
        }

        if (
            empty($this->getAuthors()) &&
            ! isset($this->fieldId) &&
            $this->getSection()->type !== SectionType::Single
        ) {
            $user = Auth::user();
            if ($user) {
                $this->setAuthor($user);
            }
        }

        if (
            ! $this->_userPostDate() &&
            (
                in_array($this->scenario, [self::SCENARIO_LIVE, self::SCENARIO_DEFAULT]) ||
                (! $this->getIsDraft() && ! $this->getIsRevision())
            )
        ) {
            // Default the post date to the current date/time
            $this->postDate = new DateTime;
            // ...without the seconds
            $this->postDate->setTimestamp($this->postDate->getTimestamp() - ($this->postDate->getTimestamp() % 60));
            // ...unless an expiry date is set in the past
            if ($this->expiryDate && $this->postDate >= $this->expiryDate) {
                $this->postDate = (clone $this->expiryDate)->modify('-1 day');
            }
        }
    }

    /**
     * @throws InvalidConfigException
     */
    #[Override]
    public function afterSave(bool $isNew): void
    {
        if (! $this->propagating) {
            $section = $this->getSection();

            // Get the entry record
            if (! $isNew) {
                $model = EntryModel::findOrFail($this->id);
            } else {
                $model = new EntryModel;
                $model->id = (int) $this->id;
            }

            $model->sectionId = $this->sectionId;
            $model->fieldId = $this->fieldId;
            $model->primaryOwnerId = $this->getPrimaryOwnerId();
            $model->typeId = $this->getType()->id;
            $model->postDate = DbHelper::prepareDateForDb($this->postDate);
            $model->expiryDate = DbHelper::prepareDateForDb($this->expiryDate);

            $status = $this->_status();
            $model->status = $status;
            // only update $this->status if it's already set, indicating that staticStatuses is enabled
            if (isset($this->status)) {
                $this->status = $status;
            }

            // Capture the dirty attributes from the record
            $dirtyAttributes = array_keys($model->getDirty());
            $model->save();

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
                (! $this->duplicateOf || $this->updatingFromDerivative || $this->placeInStructure) &&
                isset($this->sectionId) &&
                $section->type == SectionType::Structure
            ) {
                // Has the parent changed?
                if ($this->placeInStructure || $this->hasNewParent()) {
                    $this->_placeInStructure($isNew, $section);
                }

                // Update the entry’s descendants, who may be using this entry’s URI in their own URIs
                if (! $isNew && $this->getIsCanonical()) {
                    Craft::$app->getElements()->updateDescendantSlugsAndUris($this, true, true);
                }
            }
        }

        parent::afterSave($isNew);
    }

    /**
     * Save authors
     *
     * @throws Throwable
     * @throws \yii\db\Exception
     */
    private function _saveAuthors(): void
    {
        if (! isset($this->_oldAuthorIds)) {
            // Don't trust $this->_authors/_authorIds, as it may have been set to the updated value
            $this->_oldAuthorIds = DB::table(Table::ENTRIES_AUTHORS)
                ->where('entryId', $this->duplicateOf->id ?? $this->id)
                ->orderBy('sortOrder')
                ->pluck('authorId')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        DB::table(Table::ENTRIES_AUTHORS)
            ->where('entryId', $this->id)
            ->delete();

        if (! empty($this->_authorIds)) {
            $data = [];
            foreach ($this->getAuthorIds() as $sortOrder => $authorId) {
                $data[] = [
                    'entryId' => $this->id,
                    'authorId' => $authorId,
                    'sortOrder' => $sortOrder + 1,
                ];
            }

            DB::table(Table::ENTRIES_AUTHORS)
                ->insert($data);
        }
    }

    private function _placeInStructure(bool $isNew, Section $section): void
    {
        $parentId = $this->getParentId();

        // If this is a provisional draft and its new parent matches the canonical entry’s, just drop it from the structure
        if ($this->isProvisionalDraft) {
            $canonicalParentId = self::find()
                ->select(['elements.id'])
                ->ancestorOf($this->getCanonicalId())
                ->ancestorDist(1)
                ->status(null)
                ->site('*')
                ->unique()
                ->value('id');

            if ($parentId == $canonicalParentId) {
                Structures::remove($this->structureId, $this);

                return;
            }
        }

        $mode = $isNew ? Mode::Insert : Mode::Auto;

        if (! $parentId) {
            if ($section->defaultPlacement === DefaultPlacement::Beginning) {
                Structures::prependToRoot($this->structureId, $this, $mode);
            } else {
                Structures::appendToRoot($this->structureId, $this, $mode);
            }
        } else {
            if ($section->defaultPlacement === DefaultPlacement::Beginning) {
                Structures::prepend($this->structureId, $this, $this->getParent(), $mode);
            } else {
                Structures::append($this->structureId, $this, $this->getParent(), $mode);
            }
        }
    }

    #[Override]
    public function afterPropagate(bool $isNew): void
    {
        parent::afterPropagate($isNew);

        // Save a new revision?
        if ($this->_shouldSaveRevision()) {
            app(Revisions::class)->createRevision($this, $this->revisionCreatorId, $this->revisionNotes);
        }
    }

    #[Override]
    public function beforeDelete(): bool
    {
        if (! parent::beforeDelete()) {
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
                ->value('id');
            if ($parentId) {
                $data['parentId'] = $parentId;
            }
        }

        DB::table(Table::ENTRIES)
            ->where('id', $this->id)
            ->update($data);

        return true;
    }

    #[Override]
    public function afterRestore(): void
    {
        $this->deletedWithEntryType = false;
        $this->deletedWithSection = false;

        DB::table(Table::ENTRIES)
            ->where('id', $this->id)
            ->update([
                'deletedWithEntryType' => null,
                'deletedWithSection' => null,
                'dateUpdated' => now(),
            ]);

        $section = $this->getSection();
        if ($section?->type === SectionType::Structure) {
            // Add the entry back into its structure
            /** @var self|null $parent */
            $parent = self::find()
                ->structureId($section->structureId)
                ->join(new Alias(Table::ENTRIES, 'j'), 'j.parentId', '=', 'elements.id')
                ->where('j.id', $this->id)
                ->first();

            if (! $parent) {
                Structures::appendToRoot($section->structureId, $this);
            } else {
                Structures::append($section->structureId, $this, $parent);
            }
        }

        parent::afterRestore();
    }

    #[Override]
    public function afterMoveInStructure(int $structureId): void
    {
        // Was the entry moved within its section's structure?
        $section = $this->getSection();

        if ($section->type === SectionType::Structure && $section->structureId == $structureId) {
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
                $lastElement = $this;

                foreach ($drafts as $draft) {
                    Structures::moveAfter($section->structureId, $draft, $lastElement);
                    $lastElement = $draft;
                }
            }
        }

        parent::afterMoveInStructure($structureId);
    }

    /**
     * Returns whether the entry should be saving revisions on save.
     */
    private function _shouldSaveRevision(): bool
    {
        return
            $this->id &&
            ! $this->propagating &&
            ! $this->resaving &&
            ! $this->getIsDraft() &&
            ! $this->getIsRevision() &&
            $this->hasRevisions();
    }

    /**
     * Returns whether the entry’s type is allowed in its section.
     *
     * @throws InvalidConfigException
     *
     * @since 5.3.0
     */
    public function isEntryTypeCompatible(): bool
    {
        $section = $this->getSection();

        // if entry doesn't belong to a section (is nested) just allow it
        if (! $section) {
            return true;
        }

        $sectionEntryTypes = Collection::make($section->getEntryTypes())
            ->map(fn (EntryType $et) => $et->id)
            ->all();

        return in_array($this->getType()->id, $sectionEntryTypes);
    }

    /**
     * Check if current typeId is in the array of passed in entry types.
     * If no entry types are passed, check get all the available ones.
     *
     * @throws InvalidConfigException
     */
    public function isEntryTypeAllowed(?array $entryTypes = null): bool
    {
        if ($entryTypes === null) {
            $entryTypes = $this->getAvailableEntryTypes();
        }

        return in_array($this->typeId, array_map(fn ($entryType) => $entryType->id, $entryTypes));
    }

    private function handleChangedTypeId(): void
    {
        $oldLayout = EntryTypes::getEntryTypeById($this->_oldTypeId)?->getFieldLayout();
        if (! $oldLayout) {
            return;
        }

        $newFields = $this->getType()->getFieldLayout()->getCustomFields();
        $oldFields = Arr::keyBy($oldLayout->getCustomFields(), fn (FieldInterface $field) => $field->handle);
        $fieldsService = app(Fields::class);

        foreach ($newFields as $newField) {
            if (isset($oldFields[$newField->handle])) {
                $oldField = $oldFields[$newField->handle];
                if (
                    ! $fieldsService->areFieldTypesCompatible($newField::class, $oldField::class) ||
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

    #[Override]
    protected function partialTemplatePathCandidates(): array
    {
        $templates = parent::partialTemplatePathCandidates();

        $entryType = $this->getType();
        if (isset($entryType->original) && $entryType->original->handle !== $entryType->handle) {
            $templates[] = [
                'template' => sprintf(
                    '%s/%s/%s',
                    Cms::config()->partialTemplatesPath,
                    self::refHandle(),
                    $entryType->original->handle,
                ),
                'priority' => 5,
            ];
        }

        return $templates;
    }
}
