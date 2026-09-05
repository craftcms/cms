<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Concerns;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\Element\Queries\ContentBlockQuery;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Element\Queries\Exceptions\QueryAbortedException;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Query;
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

    abstract public function getFieldIdColumn(): string;

    abstract public function getPrimaryOwnerIdColumn(): string;

    public function shouldApplyNestedElementParams(): bool
    {
        return true;
    }

    /**
     * Returns whether the resulting {elements} will always have a field assigned to them.
     */
    protected function mustHaveField(): bool
    {
        return true;
    }

    /**
     * Returns whether the resulting {elements} will always have an owner assigned to them.
     */
    protected function mustHaveOwner(): bool
    {
        return true;
    }

    protected function initQueriesNestedElements(): void
    {
        $this->beforeQuery(function (AddressQuery|ContentBlockQuery|EntryQuery $elementQuery) {
            if (! $elementQuery->shouldApplyNestedElementParams()) {
                return;
            }

            $this->normalizeNestedElementParams($elementQuery);

            $mustHaveField = $this->mustHaveField();
            $mustHaveOwner = $this->mustHaveOwner();

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

            $elementQuery->query->addSelect([
                'elements_owners.ownerId as ownerId',
                'elements_owners.sortOrder as sortOrder',
            ]);

            // Only require the elements_owners row to exist if something guarantees one will match
            $joinType = $mustHaveField || $elementQuery->fieldId || $elementQuery->ownerId || $elementQuery->primaryOwnerId
                ? 'inner'
                : 'left';

            $joinClause = function (JoinClause $join) use ($elementQuery) {
                $join->on('elements_owners.elementId', '=', 'elements.id')
                    ->when(
                        $elementQuery->ownerId,
                        function (JoinClause $join) use ($elementQuery) {
                            $join->whereIn('elements_owners.ownerId', $this->normalizeOwnerId($elementQuery->ownerId));
                        },
                        function (JoinClause $join) {
                            $join->whereColumn('elements_owners.ownerId', $this->getPrimaryOwnerIdColumn());
                        },
                    );
            };

            // Join in the elements_owners table
            $elementQuery->query->join(new Alias(Table::ELEMENTS_OWNERS, 'elements_owners'), $joinClause, type: $joinType);

            if (isset($elementQuery->fieldId)) {
                if ($elementQuery->fieldId) {
                    $elementQuery->whereIn($this->getFieldIdColumn(), $elementQuery->fieldId);
                } else {
                    $elementQuery->whereNull($this->getFieldIdColumn());
                }
            }

            if (isset($elementQuery->primaryOwnerId)) {
                if ($elementQuery->primaryOwnerId) {
                    $elementQuery->whereIn($this->getPrimaryOwnerIdColumn(), $elementQuery->primaryOwnerId);
                } else {
                    $elementQuery->whereNull($this->getPrimaryOwnerIdColumn());
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
                        fn (JoinClause $join) => $join->on('owners.id', '=', $elementQuery->getPrimaryOwnerIdColumn()),
                    ),
                    type: $joinType,
                );

                if (! $allowOwnerDrafts) {
                    $elementQuery->whereNull('owners.draftId');
                }

                if (! $allowOwnerRevisions) {
                    $elementQuery->whereNull('owners.revisionId');
                }

                if ($elementQuery->ownerId === false) {
                    $elementQuery->whereNull('owners.id');
                }
            }

            $elementQuery->setNestedElementsDefaultOrderBy();
        });
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

    /**
     * Normalizes the `fieldId`, `primaryOwnerId`, and `ownerId` params.
     */
    /** @param AddressQuery|ContentBlockQuery|EntryQuery<Entry> $query */
    private function normalizeNestedElementParams(AddressQuery|ContentBlockQuery|EntryQuery $query): void
    {
        $this->normalizeFieldId($query);
        $this->primaryOwnerId = $this->normalizeOwnerId($query->primaryOwnerId);
        $this->ownerId = $this->normalizeOwnerId($query->ownerId);
    }

    /**
     * Normalizes the fieldId param to an array of IDs, false, or null
     */
    /** @param AddressQuery|ContentBlockQuery|EntryQuery<Entry> $query */
    private function normalizeFieldId(AddressQuery|ContentBlockQuery|EntryQuery $query): void
    {
        if ($query->fieldId === false) {
            return;
        }

        if (empty($query->fieldId)) {
            $query->fieldId = is_array($query->fieldId) ? [] : null;

            return;
        }

        if (is_numeric($query->fieldId)) {
            $query->fieldId = [$query->fieldId];

            return;
        }

        if (! is_array($query->fieldId) || ! Arr::isNumeric($query->fieldId)) {
            $query->fieldId = DB::table(Table::FIELDS)
                ->whereNumericParam('id', $query->fieldId)
                ->pluck('id')
                ->all();
        }
    }

    /**
     * Normalizes the primaryOwnerId param to an array of IDs, false, or null
     *
     * @return int[]|null|false
     */
    private function normalizeOwnerId(mixed $value): array|null|false
    {
        if ($value === false) {
            return false;
        }

        if (empty($value)) {
            return is_array($value) ? [] : null;
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
