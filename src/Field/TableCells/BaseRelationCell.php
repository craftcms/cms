<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\TableCells;

use Closure;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Field\Contracts\ElementAwareTableCellInterface;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use GraphQL\Type\Definition\Type;
use Override;

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
        // With the element, so it can’t be related to itself, as with relation fields
        return $this->field()->uiControl(new FieldContext($context->path, $this->normalizeValue($context->value), $context->element));
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

        return $query;
    }

    /**
     * Serializes the cell value to the IDs of the related elements that still exist, including disabled ones.
     *
     * @return list<int>
     */
    #[Override]
    public function serializeValue(mixed $value, bool $forDb = false): array
    {
        return array_map(intval(...), $this->all($this->normalizeValue($value))->ids());
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
     * @return list<ElementInterface>
     */
    public function gqlValue(ElementQueryInterface $query): array
    {
        $conditions = $this->field()->getEagerLoadingGqlConditions();
        if ($conditions === null) {
            return [];
        }

        $query = clone $query;
        foreach ($conditions as $param => $value) {
            $query->$param($value);
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
        if (is_array($value->id) && array_is_list($value->id) && array_all($value->id, fn (mixed $id): bool => is_int($id))) {
            return $value->id;
        }

        return $this->serializeValue($value);
    }

    #[Override]
    public function replaceReferences(mixed $value, array $oldTargetIds, int $newTargetId): mixed
    {
        if (! $value instanceof ElementQueryInterface) {
            return null;
        }

        $ids = $this->serializeValue($value);
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
        return (clone $query)
            ->drafts(null)
            ->status(null)
            ->site('*')
            ->unique()
            ->limit(null)
            ->eagerly(false);
    }
}
