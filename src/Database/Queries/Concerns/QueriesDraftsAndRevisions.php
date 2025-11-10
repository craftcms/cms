<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Concerns;

use craft\base\ElementInterface;
use CraftCms\Cms\Database\Queries\ElementQuery;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\User\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Tpetry\QueryExpressions\Language\Alias;

/**
 * @mixin \CraftCms\Cms\Database\Queries\ElementQuery
 *
 * @internal
 */
trait QueriesDraftsAndRevisions
{
    /**
     * @var bool Whether to replace canonical elements with provisional drafts,
     *           when they exist for the current user.
     *
     * @used-by withProvisionalDrafts()
     */
    public bool $withProvisionalDrafts = false;

    /**
     * @var bool|null Whether draft elements should be returned.
     */
    public ?bool $drafts = false;

    /**
     * @var bool|null Whether provisional drafts should be returned.
     */
    public ?bool $provisionalDrafts = false;

    /**
     * @var int|null The ID of the draft to return (from the `drafts` table)
     */
    public ?int $draftId = null;

    /**
     * @var mixed The source element ID that drafts should be returned for.
     *
     * This can be set to one of the following:
     *
     * - A source element ID – matches drafts of that element
     * - A source element
     *  - An array of source elements or element IDs
     * - `'*'` – matches drafts of any source element
     * - `false` – matches unpublished drafts that have no source element
     */
    public mixed $draftOf = null;

    /**
     * @var int|null The drafts’ creator ID
     */
    public ?int $draftCreator = null;

    /**
     * @var bool Whether only canonical elements should be included in the
     *           results, including elements that reference another canonical element via
     *           `canonicalId` so long as they aren’t a draft.
     */
    public bool $canonicalsOnly = false;

    /**
     * @var bool Whether only unpublished drafts which have been saved after initial creation should be included in the results.
     */
    public bool $savedDraftsOnly = false;

    /**
     * @var bool|null Whether revision elements should be returned.
     */
    public ?bool $revisions = false;

    /**
     * @var int|null The ID of the revision to return (from the `revisions` table)
     */
    public ?int $revisionId = null;

    /**
     * @var int|null The source element ID that revisions should be returned for
     */
    public ?int $revisionOf = null;

    /**
     * @var int|null The revisions’ creator ID
     */
    public ?int $revisionCreator = null;

    protected function initQueriesDraftsAndRevisions(): void
    {
        $this->beforeQuery(function (ElementQuery $query) {
            $this->applyDraftParams($query);
            $this->applyRevisionParams($query);
        });
    }

    private function applyDraftParams(ElementQuery $query): void
    {
        if ($this->drafts === false) {
            $query->subQuery->where($this->placeholderCondition(fn (Builder $q) => $q->whereNull('elements.draftId')));

            return;
        }

        $joinType = $this->drafts === true ? 'inner' : 'left';
        $query->subQuery->join(new Alias(Table::DRAFTS, 'drafts'), 'drafts.id', 'elements.draftId', type: $joinType);
        $query->query->join(new Alias(Table::DRAFTS, 'drafts'), 'drafts.id', 'elements.draftId', type: $joinType);

        $query->query->addSelect([
            'elements.draftId',
            'drafts.creatorId as draftCreatorId',
            'drafts.provisional as isProvisionalDraft',
            'drafts.name as draftName',
            'drafts.notes as draftNotes',
        ]);

        if ($this->draftId) {
            $query->subQuery->where('elements.draftId', $this->draftId);
        }

        if ($this->draftOf === '*') {
            $query->subQuery->whereNotNull('elements.canonicalId');
        } elseif (isset($this->draftOf)) {
            if ($this->draftOf === false) {
                $query->subQuery->whereNull('elements.canonicalId', null);
            } else {
                $query->subQuery->whereIn('elements.canonicalId', $this->draftOf);
            }
        }

        if ($this->draftCreator) {
            $query->subQuery->where('drafts.creatorId', $this->draftCreator);
        }

        if (isset($this->provisionalDrafts)) {
            $query->subQuery->where(function (Builder $query) {
                $query->whereNull('elements.draftId')
                    ->orWhere('drafts.provisional', $this->provisionalDrafts);
            });
        }

        if ($this->canonicalsOnly) {
            $query->subQuery->where(function (Builder $query) {
                $query->whereNull('elements.draftId')
                    ->orWhere(function (Builder $query) {
                        $query
                            ->whereNull('elements.canonicalId')
                            ->when(
                                $this->savedDraftsOnly,
                                fn (Builder $q) => $q->where('drafts.saved', true)
                            );
                    });
            });
        } elseif ($this->savedDraftsOnly) {
            $query->subQuery->where(function (Builder $query) {
                $query->whereNull('elements.draftId')
                    ->orWhereNotNull('elements.canonicalId')
                    ->orWhere('drafts.saved', true);
            });
        }
    }

    private function applyRevisionParams(ElementQuery $query): void
    {
        if ($this->revisions === false) {
            $query->subQuery->where($this->placeholderCondition(fn (Builder $q) => $q->whereNull('elements.revisionId')));

            return;
        }

        $joinType = $this->revisions === true ? 'inner' : 'left';
        $query->subQuery->join(new Alias(Table::REVISIONS, 'revisions'), 'revisions.id', 'elements.revisionId', type: $joinType);
        $query->query->join(new Alias(Table::REVISIONS, 'revisions'), 'revisions.id', 'elements.revisionId', type: $joinType);

        $query->query->addSelect([
            'elements.revisionId',
            'revisions.creatorId as revisionCreatorId',
            'revisions.num as revisionNum',
            'revisions.notes as revisionNotes',
        ]);

        if ($this->revisionId) {
            $query->subQuery->where('elements.revisionId', $this->revisionId);
        }

        if ($this->revisionOf) {
            $query->subQuery->where('elements.canonicalId', $this->revisionOf);
        }

        if ($this->revisionCreator) {
            $query->subQuery->where('revisions.creatorId', $this->revisionCreator);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @uses $drafts
     */
    public function drafts(?bool $value = true): static
    {
        $this->drafts = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @uses $withProvisionalDrafts
     */
    public function withProvisionalDrafts(bool $value = true): static
    {
        $this->withProvisionalDrafts = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @uses $draftId
     * @uses $drafts
     */
    public function draftId(?int $value = null): static
    {
        $this->draftId = $value;

        if ($value !== null && $this->drafts === false) {
            $this->drafts = true;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @uses $draftOf
     * @uses $drafts
     */
    public function draftOf($value): static
    {
        if ($value instanceof ElementInterface) {
            $this->draftOf = $value->getCanonicalId();

            if ($this->drafts === false) {
                $this->drafts = true;
            }

            return $this;
        }

        if (
            is_numeric($value) ||
            (is_array($value) && Arr::isNumeric($value)) ||
            $value === '*' ||
            $value === false ||
            $value === null
        ) {
            $this->draftOf = $value;

            if ($value !== null && $this->drafts === false) {
                $this->drafts = true;
            }

            return $this;
        }

        if (is_array($value) && ! empty($value)) {
            $c = Collection::make($value);
            if ($c->every(fn ($v) => $v instanceof ElementInterface || is_numeric($v))) {
                $this->draftOf = $c->map(fn ($v) => $v instanceof ElementInterface ? $v->id : $v)->all();

                if ($this->drafts === false) {
                    $this->drafts = true;
                }

                return $this;
            }
        }

        throw new InvalidArgumentException('Invalid draftOf value');
    }

    /**
     * {@inheritdoc}
     *
     * @uses $draftCreator
     * @uses $drafts
     */
    public function draftCreator($value): static
    {
        $this->draftCreator = match (true) {
            $value instanceof User => $value->id,
            is_numeric($value) || $value === null => $value,
            default => throw new InvalidArgumentException('Invalid draftCreator value'),
        };

        if ($this->draftCreator !== null && $this->drafts === false) {
            $this->drafts = true;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @uses $provisionalDrafts
     * @uses $drafts
     */
    public function provisionalDrafts(?bool $value = true): static
    {
        $this->provisionalDrafts = $value;

        if ($value === true && $this->drafts === false) {
            $this->drafts = true;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @uses $canonicalsOnly
     */
    public function canonicalsOnly(bool $value = true): static
    {
        $this->canonicalsOnly = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @uses $savedDraftsOnly
     */
    public function savedDraftsOnly(bool $value = true): static
    {
        $this->savedDraftsOnly = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @uses $revisions
     */
    public function revisions(?bool $value = true): static
    {
        $this->revisions = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @uses $revisionId
     * @uses $revisions
     */
    public function revisionId(?int $value = null): static
    {
        $this->revisionId = $value;

        if ($value !== null && $this->revisions === false) {
            $this->revisions = true;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @uses $revisionOf
     * @uses $revisions
     */
    public function revisionOf($value): static
    {
        $this->revisionOf = match (true) {
            $value instanceof ElementInterface => $value->getCanonicalId(),
            is_numeric($value) || $value === null => $value,
            default => throw new InvalidArgumentException('Invalid revisionOf value'),
        };

        if ($this->revisionOf !== null && $this->revisions === false) {
            $this->revisions = true;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @uses $revisionCreator
     * @uses $revisions
     */
    public function revisionCreator($value): static
    {
        $this->revisionCreator = match (true) {
            $value instanceof User => $value->id,
            is_numeric($value) || $value === null => $value,
            default => throw new InvalidArgumentException('Invalid revisionCreator value'),
        };

        if ($this->revisionCreator !== null && $this->revisions === false) {
            $this->revisions = true;
        }

        return $this;
    }
}
