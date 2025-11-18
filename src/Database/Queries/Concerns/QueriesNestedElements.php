<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Concerns;

use craft\base\ElementInterface;
use craft\helpers\Db;
use CraftCms\Cms\Database\Queries\ElementQuery;
use CraftCms\Cms\Database\Queries\Exceptions\QueryAbortedException;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Fields;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
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
    private ?ElementInterface $_owner = null;

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

    abstract protected function getFieldIdColumn(): string;

    abstract protected function getPrimaryOwnerIdColumn(): string;

    protected function initQueriesNestedElements(): void
    {
        $this->beforeQuery(function (ElementQuery $elementQuery) {
            /** @var ElementQuery&QueriesNestedElements $elementQuery */
            $this->normalizeNestedElementParams($elementQuery);

            if ($elementQuery->fieldId === false || $elementQuery->primaryOwnerId === false || $elementQuery->ownerId === false) {
                throw new QueryAbortedException;
            }

            if (empty($elementQuery->fieldId) && empty($elementQuery->ownerId) && empty($elementQuery->primaryOwnerId)) {
                return;
            }

            $elementQuery->query->addSelect([
                'elements_owners.ownerId',
                'elements_owners.sortOrder',
            ]);

            $joinClause = function (JoinClause $join) use ($elementQuery) {
                $join->on('elements_owners.elementId', '=', 'elements.id')
                    ->when(
                        $elementQuery->ownerId,
                        function (JoinClause $join) use ($elementQuery) {
                            $join->where('elements_owners.ownerId', $elementQuery->ownerId);
                        },
                        function (JoinClause $join) {
                            $join->whereColumn('elements_owners.ownerId', $this->getPrimaryOwnerIdColumn());
                        },
                    );
            };

            // Join in the elements_owners table
            $elementQuery->query->join(new Alias(Table::ELEMENTS_OWNERS, 'elements_owners'), $joinClause);
            $elementQuery->subQuery->join(new Alias(Table::ELEMENTS_OWNERS, 'elements_owners'), $joinClause);

            if ($elementQuery->fieldId) {
                $elementQuery->subQuery->where($this->getFieldIdColumn(), $elementQuery->fieldId);
            }

            if ($elementQuery->primaryOwnerId) {
                $this->subQuery->where($this->getPrimaryOwnerIdColumn(), $elementQuery->primaryOwnerId);
            }

            // Ignore revision/draft blocks by default
            $allowOwnerDrafts = $elementQuery->allowOwnerDrafts ?? ($elementQuery->id || $elementQuery->primaryOwnerId || $elementQuery->ownerId);
            $allowOwnerRevisions = $elementQuery->allowOwnerRevisions ?? ($elementQuery->id || $elementQuery->primaryOwnerId || $elementQuery->ownerId);

            if (! $allowOwnerDrafts || ! $allowOwnerRevisions) {
                $this->subQuery->join(
                    new Alias(Table::ELEMENTS, 'owners'),
                    fn (JoinClause $join) => $join->when(
                        $elementQuery->ownerId,
                        fn (JoinClause $join) => $join->on('owners.id', '=', 'elements_owners.ownerId'),
                        fn (JoinClause $join) => $join->on('owners.id', '=', $this->getPrimaryOwnerIdColumn()),
                    )
                );

                if (! $allowOwnerDrafts) {
                    $elementQuery->subQuery->whereNull('owners.draftId');
                }

                if (! $allowOwnerRevisions) {
                    $elementQuery->subQuery->whereNull('owners.revisionId');
                }
            }

            $this->defaultOrderBy = ['elements_owners.sortOrder' => SORT_ASC];
        });
    }

    /**
     * {@inheritdoc}
     *
     * @uses $fieldId
     */
    public function field(mixed $value): static
    {
        if (Db::normalizeParam($value, function ($item) {
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
     * {@inheritdoc}
     *
     * @uses $fieldId
     */
    public function fieldId(mixed $value): static
    {
        $this->fieldId = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @uses $primaryOwnerId
     */
    public function primaryOwnerId(mixed $value): static
    {
        $this->primaryOwnerId = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @uses $primaryOwnerId
     */
    public function primaryOwner(ElementInterface $primaryOwner): static
    {
        $this->primaryOwnerId = [$primaryOwner->id];
        $this->siteId = $primaryOwner->siteId;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @uses $ownerId
     */
    public function ownerId(mixed $value): static
    {
        $this->ownerId = $value;
        $this->_owner = null;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @uses $ownerId
     */
    public function owner(ElementInterface $owner): static
    {
        $this->ownerId = [$owner->id];
        $this->siteId = $owner->siteId;
        $this->_owner = $owner;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @uses $allowOwnerDrafts
     */
    public function allowOwnerDrafts(?bool $value = true): static
    {
        $this->allowOwnerDrafts = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @uses $allowOwnerRevisions
     */
    public function allowOwnerRevisions(?bool $value = true): static
    {
        $this->allowOwnerRevisions = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function cacheTags(): array
    {
        $tags = [];

        if ($this->fieldId) {
            foreach ($this->fieldId as $fieldId) {
                $tags[] = "field:$fieldId";
            }
        }

        if ($this->primaryOwnerId) {
            foreach ($this->primaryOwnerId as $ownerId) {
                $tags[] = "element::$ownerId";
            }
        }

        if ($this->ownerId) {
            foreach ($this->ownerId as $ownerId) {
                $tags[] = "element::$ownerId";
            }
        }

        return $tags;
    }

    /**
     * {@inheritdoc}
     */
    protected function fieldLayouts(): Collection
    {
        $this->normalizeFieldId($this);

        if ($this->fieldId) {
            $fieldLayouts = [];

            foreach ($this->fieldId as $fieldId) {
                $field = app(Fields::class)->getFieldById($fieldId);
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
    private function normalizeNestedElementParams(ElementQuery $query): void
    {
        /** @var \CraftCms\Cms\Database\Queries\EntryQuery $query */
        $this->normalizeFieldId($query);
        $this->primaryOwnerId = $this->normalizeOwnerId($query->primaryOwnerId);
        $this->ownerId = $this->normalizeOwnerId($query->ownerId);
    }

    /**
     * Normalizes the fieldId param to an array of IDs or null
     */
    private function normalizeFieldId(ElementQuery $query): void
    {
        /** @var \CraftCms\Cms\Database\Queries\EntryQuery $query */
        if ($query->fieldId === false) {
            return;
        }

        if (empty($query->fieldId)) {
            $query->fieldId = is_array($query->fieldId) ? [] : null;
        } elseif (is_numeric($query->fieldId)) {
            $query->fieldId = [$query->fieldId];
        } elseif (! is_array($query->fieldId) || ! Arr::isNumeric($query->fieldId)) {
            $query->fieldId = \Illuminate\Support\Facades\DB::table(Table::FIELDS)
                ->whereNumericParam('id', $query->fieldId)
                ->pluck('id')
                ->all();
        }
    }

    /**
     * Normalizes the primaryOwnerId param to an array of IDs or null
     *
     * @return int[]|null|false
     */
    private function normalizeOwnerId(mixed $value): array|null|false
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
