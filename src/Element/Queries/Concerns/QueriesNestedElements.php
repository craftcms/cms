<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Concerns;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\Element\Queries\ContentBlockQuery;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Element\Queries\Exceptions\QueryAbortedException;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Query;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Language\Alias;

trait QueriesNestedElements
{
    /**
     * @var mixed The field ID(s) that the resulting {elements} must belong to.
     *
     * @used-by fieldId()
     */
    public mixed $fieldId = null;

    /**
     * @var mixed The primary owner element ID(s) that the resulting {elements} must belong to.
     *
     * @used-by primaryOwner()
     * @used-by primaryOwnerId()
     */
    public mixed $primaryOwnerId = null;

    /**
     * @var mixed The owner element ID(s) that the resulting {elements} must belong to.
     *
     * @used-by owner()
     * @used-by ownerId()
     */
    public mixed $ownerId = null;

    /**
     * @var ElementInterface|null The owner element specified by [[owner()]].
     *
     * @used-by owner()
     */
    private ?ElementInterface $owner = null;

    /**
     * @var bool|null Whether the owner elements can be drafts.
     *
     * @used-by allowOwnerDrafts()
     */
    public ?bool $allowOwnerDrafts = null;

    /**
     * @var bool|null Whether the owner elements can be revisions.
     *
     * @used-by allowOwnerRevisions()
     */
    public ?bool $allowOwnerRevisions = null;

    abstract public static function getFieldIdColumn(): string;

    abstract public static function getPrimaryOwnerIdColumn(): string;

    public function shouldApplyNestedElementParams(): bool
    {
        return true;
    }

    public static function mustHaveField(): bool
    {
        return true;
    }

    public static function mustHaveOwner(): bool
    {
        return true;
    }

    protected function initQueriesNestedElements(): void
    {
        $this->beforeQuery(static function (ElementQuery $elementQuery) {
            /** @var AddressQuery|ContentBlockQuery|EntryQuery<Entry> $elementQuery */
            if (! $elementQuery->shouldApplyNestedElementParams()) {
                return;
            }

            self::normalizeNestedElementParams($elementQuery);

            $mustHaveField = $elementQuery::mustHaveField();
            $mustHaveOwner = $elementQuery::mustHaveOwner();

            if (
                ($mustHaveField && $elementQuery->fieldId === false) ||
                ($mustHaveOwner && ($elementQuery->primaryOwnerId === false || $elementQuery->ownerId === false)) ||
                $elementQuery->fieldId === [] ||
                $elementQuery->primaryOwnerId === [] ||
                $elementQuery->ownerId === []
            ) {
                throw new QueryAbortedException;
            }

            if (! isset($elementQuery->fieldId) && ! isset($elementQuery->ownerId) && ! isset($elementQuery->primaryOwnerId)) {
                return;
            }

            self::prepForNestedElementParams($elementQuery);

            if (isset($elementQuery->fieldId)) {
                self::applyFieldIdInternal($elementQuery, $elementQuery->fieldId, $elementQuery);
            }

            if (isset($elementQuery->primaryOwnerId)) {
                if ($elementQuery->primaryOwnerId) {
                    $elementQuery->whereIn($elementQuery::getPrimaryOwnerIdColumn(), $elementQuery->primaryOwnerId);
                } else {
                    $elementQuery->whereNull($elementQuery::getPrimaryOwnerIdColumn());
                }
            }

            // Ignore revision/draft blocks by default
            $allowOwnerDrafts = $elementQuery->allowOwnerDrafts ?? ($elementQuery->id || $elementQuery->primaryOwnerId || $elementQuery->ownerId);
            $allowOwnerRevisions = $elementQuery->allowOwnerRevisions ?? ($elementQuery->id || $elementQuery->primaryOwnerId || $elementQuery->ownerId);

            if (! $allowOwnerDrafts || ! $allowOwnerRevisions) {
                $elementQuery->join(
                    new Alias(Table::ELEMENTS, 'owners'),
                    fn (JoinClause $join) => $join->when(
                        $elementQuery->ownerId,
                        fn (JoinClause $join) => $join->on('owners.id', '=', 'elements_owners.ownerId'),
                        fn (JoinClause $join) => $join->on('owners.id', '=', $elementQuery::getPrimaryOwnerIdColumn()),
                    ),
                    type: self::elementsOwnersJoinType($elementQuery),
                );

                if (! $allowOwnerDrafts) {
                    $elementQuery->whereNull('owners.draftId');
                }

                if (! $allowOwnerRevisions) {
                    $elementQuery->whereNull('owners.revisionId');
                }
            }

            $elementQuery->setNestedElementsDefaultOrderBy();
        });
    }

    /** @param AddressQuery|ContentBlockQuery|EntryQuery<Entry> $elementQuery */
    public static function applyFieldId(Builder $query, mixed $value, ElementQuery $elementQuery): void
    {
        if (is_null($value)) {
            return;
        }

        self::prepForNestedElementParams($elementQuery);

        self::applyFieldIdInternal($query, $value, $elementQuery);
    }

    /** @param AddressQuery|ContentBlockQuery|EntryQuery<Entry> $elementQuery */
    private static function applyFieldIdInternal(Builder $query, mixed $value, ElementQuery $elementQuery): void
    {
        if ($value) {
            $query->whereIn($elementQuery::getFieldIdColumn(), $value);
        } else {
            $query->whereNull($elementQuery::getFieldIdColumn());
        }
    }

    public function setNestedElementsDefaultOrderBy(): void
    {
        $this->defaultOrderBy = ['elements_owners.sortOrder' => SORT_ASC];
    }

    /**
     * Narrows the query results based on the field the {elements} are contained by.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `'foo'` | in a field with a handle of `foo`.
     * | `['foo', 'bar']` | in a field with a handle of `foo` or `bar`.
     * | a [[craft\fields\Matrix]] object | in a field represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} in the Foo field #}
     * {% set {elements-var} = {twig-method}
     *   .field('foo')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} in the Foo field
     * ${elements-var} = {php-method}
     *     ->field('foo')
     *     ->all();
     * ```
     */
    public function field(mixed $value): static
    {
        if (Query::normalizeParam($value, function ($item) {
            if (is_string($item)) {
                $item = Fields::getFieldByHandle($item);
            }

            return $item instanceof ElementContainerFieldInterface ? $item->id : null;
        })) {
            $this->fieldId = $value;
        } else {
            $this->fieldId = false;
        }

        return $this;
    }

    /**
     * Narrows the query results based on the field the {elements} are contained by, per the fields’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `1` | in a field with an ID of 1.
     * | `'not 1'` | not in a field with an ID of 1.
     * | `[1, 2]` | in a field with an ID of 1 or 2.
     * | `['not', 1, 2]` | not in a field with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} in the field with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *   .fieldId(1)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} in the field with an ID of 1
     * ${elements-var} = {php-method}
     *     ->fieldId(1)
     *     ->all();
     * ```
     */
    public function fieldId(mixed $value): static
    {
        $this->fieldId = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the primary owner element of the {elements}, per the owners’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `1` | created for an element with an ID of 1.
     * | `[1, 2]` | created for an element with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} created for an element with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *   .primaryOwnerId(1)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} created for an element with an ID of 1
     * ${elements-var} = {php-method}
     *     ->primaryOwnerId(1)
     *     ->all();
     * ```
     */
    public function primaryOwnerId(mixed $value): static
    {
        $this->primaryOwnerId = $value;

        return $this;
    }

    /**
     * Sets the [[primaryOwnerId()]] and [[siteId()]] parameters based on a given element.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} created for this entry #}
     * {% set {elements-var} = {twig-method}
     *   .primaryOwner(myEntry)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} created for this entry
     * ${elements-var} = {php-method}
     *     ->primaryOwner($myEntry)
     *     ->all();
     * ```
     */
    public function primaryOwner(ElementInterface $primaryOwner): static
    {
        $this->primaryOwnerId = [$primaryOwner->id];
        $this->siteId = $primaryOwner->siteId;

        return $this;
    }

    /**
     * Narrows the query results based on the owner element of the {elements}, per the owners’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `1` | created for an element with an ID of 1.
     * | `[1, 2]` | created for an element with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} created for an element with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *   .ownerId(1)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} created for an element with an ID of 1
     * ${elements-var} = {php-method}
     *     ->ownerId(1)
     *     ->all();
     * ```
     */
    public function ownerId(mixed $value): static
    {
        $this->ownerId = $value;
        $this->owner = null;

        return $this;
    }

    /**
     * Sets the [[ownerId()]] and [[siteId()]] parameters based on a given element.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} created for this entry #}
     * {% set {elements-var} = {twig-method}
     *   .owner(myEntry)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} created for this entry
     * ${elements-var} = {php-method}
     *     ->owner($myEntry)
     *     ->all();
     * ```
     */
    public function owner(ElementInterface $owner): static
    {
        $this->ownerId = [$owner->id];
        $this->siteId = $owner->siteId;
        $this->owner = $owner;

        return $this;
    }

    /**
     * Narrows the query results based on whether the {elements}’ owners are drafts.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `true` | which can belong to a draft.
     * | `false` | which cannot belong to a draft.
     */
    public function allowOwnerDrafts(?bool $value = true): static
    {
        $this->allowOwnerDrafts = $value;

        return $this;
    }

    /**
     * Narrows the query results based on whether the {elements}’ owners are revisions.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `true` | which can belong to a revision.
     * | `false` | which cannot belong to a revision.
     */
    public function allowOwnerRevisions(?bool $value = true): static
    {
        $this->allowOwnerRevisions = $value;

        return $this;
    }

    protected function cacheTags(): array
    {
        $tags = [];

        if ($this->fieldId) {
            foreach (Arr::wrap($this->fieldId) as $fieldId) {
                $tags[] = "field:$fieldId";
            }
        }

        if ($this->primaryOwnerId) {
            foreach (Arr::wrap($this->primaryOwnerId) as $ownerId) {
                $tags[] = "element::$ownerId";
            }
        }

        if ($this->ownerId) {
            foreach (Arr::wrap($this->ownerId) as $ownerId) {
                $tags[] = "element::$ownerId";
            }
        }

        return $tags;
    }

    protected function fieldLayouts(): Collection
    {
        $this->normalizeFieldId($this);

        if ($this->fieldId) {
            $fieldLayouts = [];

            foreach ($this->fieldId as $fieldId) {
                $field = Fields::getFieldById($fieldId);
                if ($field instanceof ElementContainerFieldInterface) {
                    foreach ($field->getFieldLayoutProviders() as $provider) {
                        $fieldLayouts[] = $provider->getFieldLayout();
                    }
                }
            }

            return collect($fieldLayouts);
        }

        return parent::fieldLayouts();
    }

    /** @param AddressQuery|ContentBlockQuery|EntryQuery<Entry> $elementQuery */
    private static function prepForNestedElementParams(ElementQuery $elementQuery): void
    {
        $joinTable = new Alias(Table::ELEMENTS_OWNERS, 'elements_owners');

        if ($elementQuery->query->joinsTable($joinTable)) {
            return;
        }

        $elementQuery->query->addSelect([
            'elements_owners.ownerId as ownerId',
            'elements_owners.sortOrder as sortOrder',
        ]);

        $elementQuery->query->join(
            $joinTable,
            fn (JoinClause $join) => $join
                ->on('elements_owners.elementId', '=', 'elements.id')
                ->when(
                    $elementQuery->ownerId,
                    function (JoinClause $join) use ($elementQuery) {
                        $join->whereIn('elements_owners.ownerId', self::normalizeOwnerId($elementQuery->ownerId));
                    },
                    function (JoinClause $join) use ($elementQuery) {
                        $join->whereColumn('elements_owners.ownerId', $elementQuery::getPrimaryOwnerIdColumn());
                    },
                ),
            type: self::elementsOwnersJoinType($elementQuery),
        );
    }

    /** @param AddressQuery|ContentBlockQuery|EntryQuery<Entry> $elementQuery */
    private static function elementsOwnersJoinType(ElementQuery $elementQuery): string
    {
        // Only require the elements_owners row to exist if something guarantees one will match
        return $elementQuery::mustHaveField() || $elementQuery->fieldId || $elementQuery->ownerId || $elementQuery->primaryOwnerId
            ? 'inner'
            : 'left';
    }

    /**
     * Normalizes the `fieldId`, `primaryOwnerId`, and `ownerId` params.
     */
    /** @param AddressQuery|ContentBlockQuery|EntryQuery<Entry> $elementQuery */
    private static function normalizeNestedElementParams(ElementQuery $elementQuery): void
    {
        self::normalizeFieldId($elementQuery);
        $elementQuery->primaryOwnerId = self::normalizeOwnerId($elementQuery->primaryOwnerId);
        $elementQuery->ownerId = self::normalizeOwnerId($elementQuery->ownerId);
    }

    /**
     * Normalizes the fieldId param to an array of IDs or null
     */
    /** @param AddressQuery|ContentBlockQuery|EntryQuery<Entry> $elementQuery */
    private static function normalizeFieldId(ElementQuery $elementQuery): void
    {
        if ($elementQuery->fieldId === false) {
            return;
        }

        if (empty($elementQuery->fieldId)) {
            $elementQuery->fieldId = is_array($elementQuery->fieldId) ? [] : null;

            return;
        }

        if (is_numeric($elementQuery->fieldId)) {
            $elementQuery->fieldId = [$elementQuery->fieldId];

            return;
        }

        if (! is_array($elementQuery->fieldId) || ! Arr::isNumeric($elementQuery->fieldId)) {
            $elementQuery->fieldId = DB::table(Table::FIELDS)
                ->whereNumericParam('id', $elementQuery->fieldId)
                ->pluck('id')
                ->all();
        }
    }

    /**
     * Normalizes the primaryOwnerId param to an array of IDs or null
     *
     * @return int[]|null|false
     */
    private static function normalizeOwnerId(mixed $value): array|null|false
    {
        if (empty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return [$value];
        }

        if (! is_array($value) || ! Arr::isNumeric($value)) {
            return false;
        }

        return $value;
    }
}
