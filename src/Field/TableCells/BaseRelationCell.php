<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\TableCells;

use Closure;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Field\Contracts\ElementAwareTableCellInterface;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Gql\ArgumentManager;
use CraftCms\Cms\Gql\ElementQueryConditionBuilder;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Controls\ElementSelect;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Override;
use WeakMap;

use function CraftCms\Cms\t;

/**
 * Relation cells relate elements, as the relation field type returned by {@see fieldType()} does.
 *
 * Cells store the related elements’ IDs, and normalize to element queries for them.
 *
 * @since 6.0.0
 */
abstract class BaseRelationCell extends TableCell implements ElementAwareTableCellInterface
{
    /** @var string|list<string> The sources elements can be selected from, or `'*'` for all of them */
    public string|array $sources = BaseRelationField::ALL_SOURCES;

    /** @var array<string, mixed>|null The selectable element condition’s config */
    public ?array $selectionCondition = null;

    public ?int $minRelations = null;

    public ?int $maxRelations = null;

    public ?string $selectionLabel = null;

    private BaseRelationField $field;

    /** @var WeakMap<ElementQueryInterface, array{list<int>, list<int>}>|null Each loaded query’s IDs, and those of them that exist */
    private static ?WeakMap $existingIds = null;

    /** @var WeakMap<ElementQueryInterface, list<mixed>>|null The queries cells normalized their IDs to, with their {@see state()} then */
    private static ?WeakMap $idQueries = null;

    /**
     * Returns the relation field type whose settings, editor, and GraphQL type the cells use.
     *
     * @return class-string<BaseRelationField>
     */
    abstract public static function fieldType(): string;

    /** @param array<string, mixed>|object $config */
    public function __construct(array|object $config = [])
    {
        $config = (array) $config;

        if (array_key_exists('sources', $config) && (empty($config['sources']) || $config['sources'] === [BaseRelationField::ALL_SOURCES])) {
            $config['sources'] = BaseRelationField::ALL_SOURCES;
        }

        foreach (['minRelations', 'maxRelations'] as $attribute) {
            if (($config[$attribute] ?? null) === '') {
                $config[$attribute] = null;
            }
        }

        // A max of zero means no limit, as the cells’ queries and validation treat it
        if (is_numeric($config['maxRelations'] ?? null) && (int) $config['maxRelations'] === 0) {
            $config['maxRelations'] = null;
        }

        if (($config['selectionLabel'] ?? null) === '') {
            $config['selectionLabel'] = null;
        }

        parent::__construct($config);

        // Store the condition only when it has rules, the way relation fields do
        $this->selectionCondition = $this->field()->getSelectionCondition()?->getConfig();
    }

    #[Override]
    public static function displayName(): string
    {
        return static::fieldType()::displayName();
    }

    #[Override]
    public function settingsAttributes(): array
    {
        return array_values(array_unique([
            ...parent::settingsAttributes(),
            'sources',
            'selectionCondition',
            'minRelations',
            'maxRelations',
            'selectionLabel',
        ]));
    }

    #[Override]
    public function getRules(): array
    {
        return [
            ...parent::getRules(),
            'minRelations' => ['nullable', 'integer', 'min:0'],
            'maxRelations' => ['nullable', 'integer', 'min:0'],
        ];
    }

    #[Override]
    public function settingsUi(UiContext $context = new UiContext): ?Ui
    {
        return $this->field()->tableCellSettingsUi();
    }

    #[Override]
    public function uiControl(TableCellContext $context): Control
    {
        // With the element, so it can’t be related to itself, as with relation fields. The value is serialized
        // here, where the table checked its cells together, rather than by the field, cell by cell.
        $control = $this->field()->uiControl(new FieldContext($context->path, element: $context->element));

        return $control instanceof ElementSelect ? $control->value($this->serializeValue($context->value)) : $control;
    }

    /**
     * Normalizes the element IDs to a query for the related elements, in their order.
     *
     * The query isn’t limited to a site; the Table field queries the elements in its element’s site.
     */
    #[Override]
    public function normalizeValue(mixed $value, bool $fromRequest = false): ElementQueryInterface
    {
        if ($value instanceof ElementQueryInterface) {
            return $value;
        }

        if ($value instanceof ElementCollection) {
            $value = $value->ids()->all();
        }

        $ids = array_values(array_unique(array_filter(
            array_map(fn (mixed $id) => is_numeric($id) ? (int) $id : null, (array) $value),
        )));

        $query = static::fieldType()::elementType()::find()
            ->id($ids)
            ->fixedOrder();

        if ($this->maxRelations) {
            $query->limit($this->maxRelations);
        }

        self::$idQueries ??= new WeakMap;
        self::$idQueries[$query] = self::state($query);

        return $query;
    }

    /**
     * Returns a copy of a normalized cell value that queries the related elements in another site.
     */
    public function inSite(ElementQueryInterface $query, int $siteId): ElementQueryInterface
    {
        $clone = (clone $query)->siteId($siteId);

        if (self::idList($query) !== null) {
            self::$idQueries[$clone] = self::state($clone);
        }

        return $clone;
    }

    /**
     * Serializes the cell value to the IDs of the related elements that still exist, including disabled ones.
     *
     * @return list<int>
     */
    #[Override]
    public function serializeValue(mixed $value, bool $forDb = false): array
    {
        $query = $this->normalizeValue($value);

        // Checked along with the table’s other cells, unless the query’s IDs changed since
        $ids = self::idList($query);
        if ($ids === []) {
            return [];
        }

        if ($ids !== null && isset(self::$existingIds[$query]) && self::$existingIds[$query][0] === $ids) {
            return self::$existingIds[$query][1];
        }

        return array_map(intval(...), $this->all($query)->ids());
    }

    /**
     * Checks which of the related elements exist for many cell values with one query, for {@see serializeValue()}.
     *
     * @param  array<mixed>  $values
     */
    public function loadExistingIds(array $values): void
    {
        $queries = array_values(array_filter(
            $values,
            fn (mixed $value): bool => $value instanceof ElementQueryInterface && self::idList($value) !== null,
        ));
        if ($queries === []) {
            return;
        }

        /** @var list<list<int>> $idLists */
        $idLists = array_map(self::idList(...), $queries);
        $ids = array_values(array_unique(array_merge(...$idLists)));
        $existing = $ids === [] ? [] : array_flip(array_map(intval(...), $this->all(static::fieldType()::elementType()::find()->id($ids))->ids()));

        self::$existingIds ??= new WeakMap;
        foreach ($queries as $i => $query) {
            self::$existingIds[$query] = [$idLists[$i], array_values(array_filter($idLists[$i], fn (int $id): bool => isset($existing[$id])))];
        }
    }

    #[Override]
    public function getValueRules(): array
    {
        return [
            'array',
            // Worded as relation fields word their limits
            function (string $attribute, array $value, Closure $fail): void {
                if ($this->minRelations && count($value) < $this->minRelations) {
                    $fail(t('{attribute} should contain at least {min, number} {min, plural, one{selection} other{selections}}.', [
                        'attribute' => ':attribute',
                        'min' => $this->minRelations,
                    ]));
                } elseif ($this->maxRelations && count($value) > $this->maxRelations) {
                    $fail(t('{attribute} should contain at most {max, number} {max, plural, one{selection} other{selections}}.', [
                        'attribute' => ':attribute',
                        'max' => $this->maxRelations,
                    ]));
                }
            },
        ];
    }

    #[Override]
    public function searchKeywords(mixed $value): string
    {
        return implode(' ', array_map(
            fn (ElementInterface $element) => (string) $element,
            $this->all($this->normalizeValue($value))->all(),
        ));
    }

    #[Override]
    public function gqlType(): Type
    {
        /** @var Type $type */
        $type = $this->field()->getContentGqlType()['type'];

        return $type;
    }

    #[Override]
    public function gqlInputType(): Type
    {
        return Type::listOf(Type::int());
    }

    /**
     * Returns the related elements that the GraphQL schema allows, as relation fields do.
     *
     * With the resolve info, relations nested in the selection are eager-loaded with the schema’s conditions too,
     * as element resolvers load them.
     *
     * @param  array{argumentManager?: ArgumentManager, conditionBuilder?: ElementQueryConditionBuilder}|null  $context
     * @return list<ElementInterface>
     */
    public function gqlValue(ElementQueryInterface $query, ?ResolveInfo $resolveInfo = null, ?array $context = null): array
    {
        $conditions = $this->field()->getEagerLoadingGqlConditions();
        if ($conditions === null) {
            return [];
        }

        $query = clone $query;
        foreach ($conditions as $param => $value) {
            $query->$param($value);
        }

        if ($resolveInfo !== null) {
            $conditionBuilder = $context['conditionBuilder'] ?? new ElementQueryConditionBuilder;
            $conditionBuilder->setResolveInfo($resolveInfo);
            $conditionBuilder->setArgumentManager($context['argumentManager'] ?? new ArgumentManager);

            foreach ($conditionBuilder->extractQueryConditions() as $method => $parameters) {
                if (method_exists($query, $method)) {
                    $query->$method($parameters);
                }
            }
        }

        // Capped as element resolvers cap their queries
        $maxGraphqlResults = Cms::config()->maxGraphqlResults;
        if ($maxGraphqlResults > 0) {
            $query->limit($query->getLimit() === null ? $maxGraphqlResults : min($maxGraphqlResults, (int) $query->getLimit()));
        }

        return $query->all();
    }

    #[Override]
    public function localizeValues(array $values, ElementInterface $element): array
    {
        // Relations point at elements, not their sites; each site queries them in its own site
        return $values;
    }

    /**
     * Relations are stored in the `relations` table rather than as field references.
     *
     * @see relationTargetIds()
     */
    #[Override]
    public function referenceTargetIds(mixed $value): array
    {
        return [];
    }

    /**
     * Returns the IDs of the elements a normalized cell value relates to, for the `relations` table.
     *
     * @return list<int>
     */
    public function relationTargetIds(mixed $value): array
    {
        if (! $value instanceof ElementQueryInterface) {
            return [];
        }

        // The IDs the value was normalized from, without querying for them, as relation fields do
        return self::idList($value) ?? $this->serializeValue($value);
    }

    /**
     * Returns what a query filters by: its params, and the clauses added to its query builder.
     *
     * @return list<mixed>
     */
    private static function state(ElementQueryInterface $query): array
    {
        $state = [$query->getCriteria()];

        if ($query instanceof ElementQuery) {
            $builder = $query->getQuery();
            array_push($state, $builder->wheres, $builder->joins, $builder->havings, $builder->groups, $builder->unions, $builder->beforeQueryCallbacks);
        }

        return $state;
    }

    /**
     * Returns the IDs a cell normalized a query from, or `null` for queries set some other way or given other
     * criteria since, which may filter the elements further.
     *
     * @return list<int>|null
     */
    private static function idList(ElementQueryInterface $query): ?array
    {
        if (! isset(self::$idQueries[$query]) || self::$idQueries[$query] !== self::state($query)) {
            return null;
        }

        $ids = $query->id;

        return is_array($ids) && array_is_list($ids) && array_all($ids, fn (mixed $id): bool => is_int($id)) ? $ids : null;
    }

    #[Override]
    public function replaceReferences(mixed $value, array $oldTargetIds, int $newTargetId): mixed
    {
        if (! $value instanceof ElementQueryInterface) {
            return null;
        }

        // Without querying, so targets that were deleted are still replaced
        $ids = $this->relationTargetIds($value);
        if (array_intersect($ids, $oldTargetIds) === []) {
            return null;
        }

        return $this->normalizeValue(array_map(
            fn (int $id) => in_array($id, $oldTargetIds, true) ? $newTargetId : $id,
            $ids,
        ));
    }

    /**
     * Returns the relation field the cells’ settings, editor, and GraphQL type come from.
     */
    protected function field(): BaseRelationField
    {
        return $this->field ??= new (static::fieldType())($this->fieldConfig());
    }

    /**
     * Returns the config for {@see field()}.
     *
     * @return array<string, mixed>
     */
    protected function fieldConfig(): array
    {
        return [
            'sources' => $this->sources,
            'selectionCondition' => $this->selectionCondition,
            'minRelations' => $this->minRelations,
            'maxRelations' => $this->maxRelations,
            'selectionLabel' => $this->selectionLabel,
        ];
    }

    /**
     * Returns a clone of the query that includes disabled and cross-site elements.
     */
    private function all(ElementQueryInterface $query): ElementQueryInterface
    {
        $all = (clone $query)
            ->drafts(null)
            ->status(null)
            ->site('*')
            ->unique()
            ->limit(null)
            ->eagerly(false);

        // Prefer the query’s site over the current one
        if ($query->siteId !== null) {
            $all->preferSites([$query->siteId]);
        }

        return $all;
    }
}
