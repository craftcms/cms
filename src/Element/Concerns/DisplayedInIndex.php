<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\ElementAttributeRenderer;
use CraftCms\Cms\Element\Enums\ElementIndexViewMode;
use CraftCms\Cms\Element\Events\ElementCardAttributesResolving;
use CraftCms\Cms\Element\Events\ElementDefaultCardAttributesResolving;
use CraftCms\Cms\Element\Events\ElementDefaultTableAttributesResolving;
use CraftCms\Cms\Element\Events\ElementSearchableAttributesResolving;
use CraftCms\Cms\Element\Events\ElementSortOptionsResolving;
use CraftCms\Cms\Element\Events\ElementTableAttributesResolving;
use CraftCms\Cms\Element\Events\QueryForTableAttributePreparing;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\ExcludeDescendantIdsExpression;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Drafts;
use CraftCms\Cms\Support\Facades\ElementSources;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Structures;
use Illuminate\Contracts\Database\Query\Expression as ExpressionInterface;
use Illuminate\Support\Facades\DB;
use Stringable;
use Tpetry\QueryExpressions\Function\Conditional\Coalesce;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * DisplayedInIndex provides element index display functionality.
 *
 * This trait contains methods for defining how elements are displayed in the control panel
 * index view, including table attributes, sort options, card attributes, and searchable attributes.
 *
 * @internal
 */
trait DisplayedInIndex
{
    /**
     * @var string|null The view mode used to show this element (e.g. `structure`, `table`, `thumbs`, `cards`).
     */
    public ?string $viewMode = null;

    /**
     * Returns the attributes that should be searchable for this element type.
     *
     * @return string[] The searchable attributes
     */
    final public static function searchableAttributes(): array
    {
        event($event = new ElementSearchableAttributesResolving(
            elementType: static::class,
            attributes: static::defineSearchableAttributes(),
        ));

        return $event->attributes;
    }

    /**
     * Defines which element attributes should be searchable.
     *
     * @return string[] The element attributes that should be searchable
     *
     * @see searchableAttributes()
     */
    protected static function defineSearchableAttributes(): array
    {
        return [];
    }

    /**
     * Returns the attributes that should not be duplicated when bulk duplicating elements.
     *
     * @return array<string,mixed> The attribute names (as keys) and their replacement values
     */
    public static function baseBulkDuplicateAttributes(): array
    {
        $attributes = [
            'structureId' => null,
            'root' => null,
            'lft' => null,
            'rgt' => null,
            'level' => null,
        ];

        if (is_subclass_of(static::class, NestedElementInterface::class)) {
            $attributes += [
                'fieldId' => null,
                'ownerId' => null,
                'primaryOwnerId' => null,
                'sortOrder' => null,
            ];
        }

        return $attributes;
    }

    /**
     * Returns the HTML for the element index view.
     *
     * @param  ElementQueryInterface  $elementQuery  The element query
     * @param  int[]|null  $disabledElementIds  The disabled element IDs
     * @param  array<string,mixed>  $viewState  The view state
     * @param  string|null  $sourceKey  The source key
     * @param  string|null  $context  The context
     * @param  bool  $includeContainer  Whether to include the container
     * @param  bool  $selectable  Whether the elements are selectable
     * @param  bool  $sortable  Whether the elements are sortable
     * @return string|Stringable The HTML
     */
    public static function indexHtml(
        ElementQueryInterface $elementQuery,
        ?array $disabledElementIds,
        array $viewState,
        ?string $sourceKey,
        ?string $context,
        bool $includeContainer,
        bool $selectable,
        bool $sortable,
    ): string|Stringable {
        $variables = static::indexData(
            elementQuery: $elementQuery,
            disabledElementIds: $disabledElementIds,
            viewState: $viewState,
            sourceKey: $sourceKey,
            context: $context,
            selectable: $selectable,
            sortable: $sortable,
        );

        if (empty($variables['elements']) && ! $includeContainer) {
            // load-more request
            return '';
        }

        $template = '_elements/'.$viewState['mode'].'view/'.($includeContainer ? 'container' : 'elements');

        return template($template, $variables);
    }

    /**
     * Builds the data (template variables) for the element index view.
     *
     * This applies the index's ordering and table-attribute preparation to the
     * given query, resolves the resulting elements, and returns the variables
     * that {@see indexHtml()} renders — letting callers (e.g. Inertia
     * controllers) consume the same data without rendering the template.
     *
     * @param  ElementQueryInterface  $elementQuery  The element query
     * @param  int[]|null  $disabledElementIds  The disabled element IDs
     * @param  array<string,mixed>  $viewState  The view state
     * @param  string|null  $sourceKey  The source key
     * @param  string|null  $context  The context
     * @param  bool  $selectable  Whether the elements are selectable
     * @param  bool  $sortable  Whether the elements are sortable
     * @return array<string,mixed> The template variables
     */
    public static function indexData(
        ElementQueryInterface $elementQuery,
        ?array $disabledElementIds,
        array $viewState,
        ?string $sourceKey,
        ?string $context,
        bool $selectable,
        bool $sortable,
    ): array {
        $static = $viewState['static'] ?? false;
        $variables = [
            'viewMode' => $viewState['mode'],
            'context' => $context,
            'disabledElementIds' => $disabledElementIds,
            'collapsedElementIds' => request()->input('collapsedElementIds'),
            'selectable' => ! $static && $selectable,
            'sortable' => ! $static && $sortable,
            'showHeaderColumn' => $viewState['showHeaderColumn'] ?? false,
            'inlineEditing' => $viewState['inlineEditing'] ?? false,
            'nestedInputNamespace' => $viewState['nestedInputNamespace'] ?? null,
            'tableName' => static::pluralDisplayName(),
            'elementQuery' => self::elementQueryWithAllDescendants($elementQuery),
            'returnUrl' => $viewState['returnUrl'] ?? null,
        ];

        if (! empty($viewState['order'])) {
            // Special case for sorting by structure
            if ($viewState['order'] === 'structure') {
                $source = ElementSources::findSource(static::class, $sourceKey, $context);

                if (isset($source['structureId'])) {
                    $elementQuery
                        ->structureId($source['structureId'])
                        ->orderBy('lft');
                    $variables['structure'] = Structures::getStructureById($source['structureId']);

                    // Are they allowed to make changes to this structure?
                    if (in_array($context, ['index', 'embedded-index']) && $variables['structure'] && ! empty($source['structureEditable'])) {
                        $variables['structureEditable'] = true;

                        // Let StructuresController know that this user can make changes to the structure
                        SessionAuth::authorize('editStructure:'.$variables['structure']->id);
                    }
                } else {
                    unset($viewState['order']);
                }
            } elseif ($orderBy = self::_indexOrderBy($sourceKey, $viewState['order'], $viewState['sort'] ?? 'asc')) {
                self::applyIndexOrderBy($elementQuery, $orderBy);

                if ((! is_array($orderBy) || ! isset($orderBy['score'])) && ! empty($viewState['orderHistory'])) {
                    foreach ($viewState['orderHistory'] as $order) {
                        if ($order[0] && $orderBy = self::_indexOrderBy($sourceKey, $order[0], $order[1])) {
                            self::applyIndexOrderBy($elementQuery, $orderBy);
                        } else {
                            break;
                        }
                    }
                }
            }
        }

        if ($viewState['mode'] === 'table') {
            // Get the table columns
            $variables['attributes'] = ElementSources::getTableAttributes(
                elementType: static::class,
                sourceKey: $sourceKey,
                customAttributes: $viewState['tableColumns'] ?? null,
                fieldLayouts: $viewState['fieldLayouts'] ?? null,
            );

            // Prepare the element query for each of the table attributes
            foreach ($variables['attributes'] as $attribute) {
                event($event = new QueryForTableAttributePreparing(
                    elementType: static::class,
                    query: $elementQuery,
                    attribute: $attribute[0],
                ));

                if ($event->handled) {
                    continue;
                }

                static::prepElementQueryForTableAttribute($elementQuery, $attribute[0]);
            }

            if (! $variables['showHeaderColumn'] && count($variables['attributes']) <= 1) {
                $variables['showHeaderColumn'] = true;
            }
        }

        // Only cache if there's no search term or relation param
        if (! $elementQuery->search) {
            $elementQuery->cache();
        }

        $elements = static::indexElements($elementQuery, $sourceKey);

        // See if there are any provisional changes we should show
        Drafts::loadProvisionalChanges($elements);

        if (request()->boolean('prevalidate')) {
            foreach ($elements as $element) {
                if ($element->enabled && $element->getEnabledForSite()) {
                    $element->ruleset->useScenario(ElementRules::SCENARIO_LIVE);
                }
                $element->validate();
            }
        }

        foreach ($elements as $element) {
            $element->viewMode = $viewState['mode'];
        }

        $variables['elements'] = $elements;

        return $variables;
    }

    /**
     * Applies a normalized element index ordering to the element query.
     */
    /** @param array<array-key,mixed>|ExpressionInterface $orderBy */
    private static function applyIndexOrderBy(ElementQueryInterface $elementQuery, ExpressionInterface|array $orderBy): void
    {
        foreach (Arr::wrap($orderBy) as $column => $direction) {
            if ($direction instanceof ExpressionInterface) {
                $elementQuery->getQuery()->orderByRaw(
                    $direction->getValue(DB::getQueryGrammar()),
                );

                continue;
            }

            $elementQuery->getQuery()->orderBy($column, match ($direction) {
                'desc', SORT_DESC => 'desc',
                default => 'asc',
            });
        }
    }

    /**
     * Returns an element query without descendant ID exclusions.
     *
     * @param  ElementQueryInterface  $elementQuery  The element query
     * @return ElementQueryInterface The modified element query
     */
    private static function elementQueryWithAllDescendants(ElementQueryInterface $elementQuery): ElementQueryInterface
    {
        $wheres = $elementQuery->getQuery()->wheres;

        if (! is_array($wheres)) {
            return $elementQuery;
        }

        foreach ($wheres as $key => $where) {
            $column = $where['column'] ?? null;

            if (! $column instanceof ExcludeDescendantIdsExpression) {
                continue;
            }

            $elementQuery = clone $elementQuery;
            unset($wheres[$key]);
            $elementQuery->getQuery()->wheres = $wheres;

            return $elementQuery;
        }

        return $elementQuery;
    }

    /**
     * Prepares an element query for an element index that includes a given table attribute.
     *
     * @param  ElementQueryInterface  $elementQuery  The element query
     * @param  string  $attribute  The attribute name
     */
    protected static function prepElementQueryForTableAttribute(
        ElementQueryInterface $elementQuery,
        string $attribute,
    ): void {
        match ($attribute) {
            'ancestors' => $elementQuery->andWith(['ancestors', ['status' => null]]),
            'parent' => $elementQuery->andWith(['parent', ['status' => null]]),
            'revisionNotes' => $elementQuery->andWith('currentRevision'),
            'revisionCreator' => $elementQuery->andWith('currentRevision.revisionCreator'),
            'drafts' => $elementQuery->andWith(['drafts', ['status' => null, 'orderBy' => ['dateUpdated' => SORT_DESC]]]),
            default => self::prepCustomFieldQuery($elementQuery, $attribute),
        };
    }

    /**
     * Prepares custom field queries for element indexes.
     *
     * @param  ElementQueryInterface  $elementQuery  The element query
     * @param  string  $attribute  The attribute name
     */
    private static function prepCustomFieldQuery(ElementQueryInterface $elementQuery, string $attribute): void
    {
        if (preg_match('/^field:(.+)/', $attribute, $matches)) {
            Fields::getFieldByUid($matches[1])?->modifyElementIndexQuery($elementQuery);
        }
    }

    /**
     * Returns the resulting elements for an element index.
     *
     * @param  ElementQueryInterface  $elementQuery  The element query
     * @param  string|null  $sourceKey  The source key
     * @return ElementInterface[] The elements
     */
    public static function indexElements(ElementQueryInterface $elementQuery, ?string $sourceKey): array
    {
        return $elementQuery->all();
    }

    /**
     * Returns the total number of elements for an element index.
     *
     * @param  ElementQueryInterface  $elementQuery  The element query
     * @param  string|null  $sourceKey  The source key
     * @return int The element count
     */
    public static function indexElementCount(ElementQueryInterface $elementQuery, ?string $sourceKey): int
    {
        return $elementQuery->getCountForPagination();
    }

    /**
     * Returns the available view modes for the element index.
     */
    /** @return array<array-key,mixed> */
    public static function indexViewModes(): array
    {
        return array_values(array_filter([
            array_merge(ElementIndexViewMode::Structure->toArray(), [
                'structuresOnly' => true,
            ]),
            array_merge(ElementIndexViewMode::Table->toArray(), [
                'availableOnMobile' => false,
            ]),
            static::hasThumbs() ? ElementIndexViewMode::Thumbs->toArray() : null,
            ElementIndexViewMode::Cards->toArray(),
        ]));
    }

    /** @return array<array-key,mixed> */
    public static function sortOptions(): array
    {
        $sortOptions = static::defineSortOptions();

        // Make sure ID is listed first
        $sortOptions = [
            'id' => t('ID'),
            ...Arr::except($sortOptions, 'id'),
        ];

        event($event = new ElementSortOptionsResolving(
            elementType: static::class,
            sortOptions: $sortOptions,
        ));

        return $event->sortOptions;
    }

    /**
     * Returns the sort options for the element type.
     *
     * @return array<array-key,mixed> The attributes that elements can be sorted by
     *
     * @see sortOptions()
     */
    protected static function defineSortOptions(): array
    {
        // Default to the available table attributes
        return ElementSources::getAvailableTableAttributes(static::class)
            ->map(fn (array $labelInfo) => $labelInfo['label'])
            ->all();
    }

    /** @return array<string,array<string,mixed>> */
    public static function tableAttributes(): array
    {
        event($event = new ElementTableAttributesResolving(
            elementType: static::class,
            tableAttributes: static::defineTableAttributes(),
        ));

        return $event->tableAttributes;
    }

    /**
     * Defines all of the available columns that can be shown in table views.
     *
     * @return array<string,array<string,mixed>> The table attributes.
     *
     * @see tableAttributes()
     */
    protected static function defineTableAttributes(): array
    {
        $attributes = [
            'dateCreated' => ['label' => t('Date Created')],
            'dateUpdated' => ['label' => t('Date Updated')],
            'id' => ['label' => t('ID')],
            'uid' => ['label' => t('UID')],
        ];

        if (static::hasStatuses()) {
            $attributes['status'] = ['label' => t('Status')];
        }

        if (static::hasUris()) {
            return array_merge($attributes, [
                'link' => ['label' => t('Link'), 'icon' => 'world'],
                'slug' => ['label' => t('Slug')],
                'uri' => ['label' => t('URI')],
            ]);
        }

        return $attributes;
    }

    /**
     * Returns the default table attributes for a source.
     *
     * @param  string  $source  The source key
     * @return string[] The default table attribute keys
     */
    public static function defaultTableAttributes(string $source): array
    {
        event($event = new ElementDefaultTableAttributesResolving(
            elementType: static::class,
            source: $source,
            tableAttributes: static::defineDefaultTableAttributes($source),
        ));

        return $event->tableAttributes;
    }

    /**
     * Returns the list of table attribute keys that should be shown by default.
     *
     * @param  string  $source  The selected source's key
     * @return string[] The table attributes.
     *
     * @see defaultTableAttributes()
     * @see tableAttributes()
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        // Return all of them by default
        return array_keys(static::tableAttributes());
    }

    /**
     * Returns the card attributes for the element type.
     *
     * @param  FieldLayout|null  $fieldLayout  The field layout
     * @return array<string,array<string,mixed>> The card attributes
     */
    public static function cardAttributes(?FieldLayout $fieldLayout = null): array
    {
        event($event = new ElementCardAttributesResolving(
            elementType: static::class,
            cardAttributes: static::defineCardAttributes(),
            fieldLayout: $fieldLayout,
        ));

        return $event->cardAttributes;
    }

    /**
     * Defines all the available attributes that can be shown in card views along with their default placeholder values.
     *
     * @return array<string,array<string,mixed>> The card attributes.
     *
     * @see cardAttributes()
     */
    protected static function defineCardAttributes(): array
    {
        // we're intentionally not including statuses as those already show in cards
        $attributes = [
            'dateCreated' => [
                'label' => t('Date Created'),
                'placeholder' => fn () => now()->subDays(16),
            ],
            'dateUpdated' => [
                'label' => t('Date Updated'),
                'placeholder' => fn () => now()->subDays(15),
            ],
            'id' => [
                'label' => t('ID'),
                'placeholder' => fn () => 4321,
            ],
            'uid' => [
                'label' => t('UID'),
                'placeholder' => fn () => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
            ],
        ];

        if (static::hasUris()) {
            return array_merge($attributes, [
                'link' => [
                    'label' => t('Link'),
                    'placeholder' => fn () => app(ElementAttributeRenderer::class)->linkAttributeHtml('#'),
                ],
                'slug' => [
                    'label' => t('Slug'),
                    'placeholder' => fn () => t('Slug'),
                ],
                'uri' => [
                    'label' => t('URI'),
                    'placeholder' => fn () => app(ElementAttributeRenderer::class)->uriAttributeHtml(t('link/to/something'), '#'),
                ],
            ]);
        }

        return $attributes;
    }

    /**
     * Returns the preview HTML for a card attribute.
     *
     * @param  array<string,mixed>  $attribute  The attribute configuration
     * @return mixed The preview HTML
     */
    public static function attributePreviewHtml(array $attribute): mixed
    {
        return match ($attribute['value']) {
            'link', 'uri' => $attribute['placeholder'],
            default => app(ElementAttributeRenderer::class)->attributeHtml(is_callable($attribute['placeholder'] ?? null)
                ? $attribute['placeholder']()
                : $attribute['placeholder'] ?? $attribute['label'],
            ),
        };
    }

    /**
     * Returns the default card attributes.
     *
     * @return string[] The default card attribute keys
     */
    public static function defaultCardAttributes(): array
    {
        event($event = new ElementDefaultCardAttributesResolving(
            elementType: static::class,
            cardAttributes: static::defineDefaultCardAttributes(),
        ));

        return $event->cardAttributes;
    }

    /**
     * Returns the list of card attribute keys that should be shown by default.
     *
     * @return string[] The card attributes.
     *
     * @see defaultCardAttributes()
     * @see cardAttributes()
     */
    protected static function defineDefaultCardAttributes(): array
    {
        return [];
    }

    /**
     * Returns the orderBy value for element indexes.
     *
     * @param  string  $sourceKey  The source key
     * @param  string  $attribute  The attribute to sort by
     * @param  string  $dir  The sort direction ('asc' or 'desc')
     * @return ExpressionInterface|array<array-key,mixed>|false The order by clause
     */
    private static function _indexOrderBy(
        string $sourceKey,
        string $attribute,
        string $dir,
    ): ExpressionInterface|array|false {
        $sortDirection = strcasecmp($dir, 'desc') === 0 ? SORT_DESC : SORT_ASC;
        $columns = self::_indexOrderByColumns($sourceKey, $attribute, $sortDirection);

        if ($columns === false || $columns instanceof ExpressionInterface) {
            return $columns;
        }

        $columns = is_string($columns)
            ? preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY)
            : $columns;

        return self::normalizeOrderByColumns($columns, $sortDirection);
    }

    /**
     * Normalizes order by columns with their sort directions.
     *
     * @param  array<array-key,mixed>  $columns  The columns to normalize
     * @param  int  $defaultDirection  The default sort direction
     * @return array<array-key,int> The normalized columns with directions
     */
    private static function normalizeOrderByColumns(array $columns, int $defaultDirection): array
    {
        $result = [];

        foreach ($columns as $i => $column) {
            if ($i === 0) {
                // The first column's sort direction is always user-defined
                $result[$column] = $defaultDirection;

                continue;
            }

            if (preg_match('/^(.*?)\s+(asc|desc)$/i', (string) $column, $matches)) {
                $result[$matches[1]] = strcasecmp($matches[2], 'desc') === 0 ? SORT_DESC : SORT_ASC;

                continue;
            }

            $result[$column] = SORT_ASC;
        }

        return $result;
    }

    /**
     * Returns the columns for element index ordering.
     *
     * @param  string  $sourceKey  The source key
     * @param  string  $attribute  The attribute to sort by
     * @param  int  $dir  The sort direction (SORT_ASC or SORT_DESC)
     * @return ExpressionInterface|bool|array<array-key,mixed>|string The columns
     */
    private static function _indexOrderByColumns(
        string $sourceKey,
        string $attribute,
        int $dir,
    ): ExpressionInterface|bool|array|string {
        if (! $attribute) {
            return false;
        }

        if ($attribute === 'score') {
            return 'score';
        }

        // Check element's own sort options
        if ($orderBy = self::resolveSortOption($attribute, $dir)) {
            return $orderBy;
        }

        // Check source-specific sort options
        return self::resolveSourceSortOption($sourceKey, $attribute, $dir);
    }

    /**
     * Resolves the orderBy value from the element's sort options.
     *
     * @param  string  $attribute  The attribute to sort by
     * @param  int  $dir  The sort direction
     * @return ExpressionInterface|array<array-key,mixed>|string|false The orderBy value
     */
    private static function resolveSortOption(string $attribute, int $dir): ExpressionInterface|array|string|false
    {
        foreach (static::sortOptions() as $key => $sortOption) {
            if (! is_array($sortOption) && $key === $attribute) {
                return $key;
            }

            if (is_array($sortOption)) {
                $optionAttribute = $sortOption['attribute'] ?? $sortOption['orderBy'];
                if ($optionAttribute === $attribute) {
                    return is_callable($sortOption['orderBy'])
                        ? $sortOption['orderBy']($dir)
                        : $sortOption['orderBy'];
                }
            }
        }

        return false;
    }

    /**
     * Resolves the orderBy value from source-specific sort options.
     *
     * @param  string  $sourceKey  The source key
     * @param  string  $attribute  The attribute to sort by
     * @param  int  $dir  The sort direction
     * @return ExpressionInterface|bool The orderBy value
     */
    private static function resolveSourceSortOption(string $sourceKey, string $attribute, int $dir): ExpressionInterface|bool
    {
        $sourceSortOptions = ElementSources::getSourceSortOptions(static::class, $sourceKey);

        foreach ($sourceSortOptions as $sortOption) {
            if ($sortOption['attribute'] !== $attribute) {
                continue;
            }

            $orderBy = $sortOption['orderBy'];

            if ($orderBy instanceof Coalesce) {
                $sql = $orderBy->getValue(DB::getQueryGrammar());
            } elseif (is_string($orderBy)) {
                $sql = $orderBy;
            } else {
                return $orderBy;
            }

            $direction = $dir === SORT_ASC ? 'ASC' : 'DESC';

            return DB::raw("$sql $direction");
        }

        return false;
    }
}
