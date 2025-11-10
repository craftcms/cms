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
            'elements.draftId as draftId',
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
            'elements.revisionId as revisionId',
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
     * Narrows the query results to only drafts {elements}.
     *
     * ---
     *
     * ```twig
     * {# Fetch a draft {element} #}
     * {% set {elements-var} = {twig-method}
     *   .drafts()
     *   .id(123)
     *   .one() %}
     * ```
     *
     * ```php
     * // Fetch a draft {element}
     * ${elements-var} = {element-class}::find()
     *     ->drafts()
     *     ->id(123)
     *     ->one();
     * ```
     */
    public function drafts(?bool $value = true): static
    {
        $this->drafts = $value;

        return $this;
    }

    /**
     * Causes the query to return provisional drafts for the matching elements,
     * when they exist for the current user.
     */
    public function withProvisionalDrafts(bool $value = true): static
    {
        $this->withProvisionalDrafts = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the {elements}’ draft’s ID (from the `drafts` table).
     *
     * Possible values include:
     *
     * | Value | Fetches drafts…
     * | - | -
     * | `1` | for the draft with an ID of 1.
     *
     * ---
     *
     * ```twig
     * {# Fetch a draft #}
     * {% set {elements-var} = {twig-method}
     *   .draftId(10)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch a draft
     * ${elements-var} = {php-method}
     *     ->draftId(10)
     *     ->all();
     * ```
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
     * Narrows the query results to only drafts of a given {element}.
     *
     * Possible values include:
     *
     * | Value | Fetches drafts…
     * | - | -
     * | `1` | for the {element} with an ID of 1.
     * | `[1, 2]` | for the {elements} with an ID of 1 or 2.
     * | a [[{element-class}]] object | for the {element} represented by the object.
     * | an array of [[{element-class}]] objects | for the {elements} represented by the objects.
     * | `'*'` | for any {element}
     * | `false` | that aren’t associated with a published {element}
     *
     * ---
     *
     * ```twig
     * {# Fetch drafts of the {element} #}
     * {% set {elements-var} = {twig-method}
     *   .draftOf({myElement})
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch drafts of the {element}
     * ${elements-var} = {php-method}
     *     ->draftOf(${myElement})
     *     ->all();
     * ```
     */
    public function draftOf(mixed $value): static
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
     * Narrows the query results to only drafts created by a given user.
     *
     * Possible values include:
     *
     * | Value | Fetches drafts…
     * | - | -
     * | `1` | created by the user with an ID of 1.
     * | a [[User]] object | created by the user represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch drafts by the current user #}
     * {% set {elements-var} = {twig-method}
     *   .draftCreator(currentUser)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch drafts by the current user
     * ${elements-var} = {php-method}
     *     ->draftCreator(Auth::user())
     *     ->all();
     * ```
     */
    public function draftCreator(mixed $value): static
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
     * Narrows the query results to only provisional drafts.
     *
     * ---
     *
     * ```twig
     * {# Fetch provisional drafts created by the current user #}
     * {% set {elements-var} = {twig-method}
     *   .provisionalDrafts()
     *   .draftCreator(currentUser)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch provisional drafts created by the current user
     * ${elements-var} = {php-method}
     *     ->provisionalDrafts()
     *     ->draftCreator(Auth::user())
     *     ->all();
     * ```
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
     * Narrows the query results to only canonical elements, including elements
     * that reference another canonical element via `canonicalId` so long as they
     * aren’t a draft.
     *
     * Unpublished drafts can be included as well if `drafts(null)` and
     * `draftOf(false)` are also passed.
     */
    public function canonicalsOnly(bool $value = true): static
    {
        $this->canonicalsOnly = $value;

        return $this;
    }

    /**
     * Narrows the query results to only unpublished drafts which have been saved after initial creation.
     *
     * ---
     *
     * ```twig
     * {# Fetch saved, unpublished draft {elements} #}
     * {% set {elements-var} = {twig-method}
     *   .draftOf(false)
     *   .savedDraftsOnly()
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch saved, unpublished draft {elements}
     * ${elements-var} = {element-class}::find()
     *     ->draftOf(false)
     *     ->savedDraftsOnly()
     *     ->all();
     * ```
     */
    public function savedDraftsOnly(bool $value = true): static
    {
        $this->savedDraftsOnly = $value;

        return $this;
    }

    /**
     * Narrows the query results to only revision {elements}.
     *
     * ---
     *
     * ```twig
     * {# Fetch a revision {element} #}
     * {% set {elements-var} = {twig-method}
     *   .revisions()
     *   .id(123)
     *   .one() %}
     * ```
     *
     * ```php
     * // Fetch a revision {element}
     * ${elements-var} = {element-class}::find()
     *     ->revisions()
     *     ->id(123)
     *     ->one();
     * ```
     */
    public function revisions(?bool $value = true): static
    {
        $this->revisions = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the {elements}’ revision’s ID (from the `revisions` table).
     *
     * Possible values include:
     *
     * | Value | Fetches revisions…
     * | - | -
     * | `1` | for the revision with an ID of 1.
     *
     * ---
     *
     * ```twig
     * {# Fetch a revision #}
     * {% set {elements-var} = {twig-method}
     *   .revisionId(10)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch a revision
     * ${elements-var} = {php-method}
     *     ->revisionIf(10)
     *     ->all();
     * ```
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
     * Narrows the query results to only revisions of a given {element}.
     *
     * Possible values include:
     *
     * | Value | Fetches revisions…
     * | - | -
     * | `1` | for the {element} with an ID of 1.
     * | a [[{element-class}]] object | for the {element} represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch revisions of the {element} #}
     * {% set {elements-var} = {twig-method}
     *   .revisionOf({myElement})
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch revisions of the {element}
     * ${elements-var} = {php-method}
     *     ->revisionOf(${myElement})
     *     ->all();
     * ```
     */
    public function revisionOf(mixed $value): static
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
     * Narrows the query results to only revisions created by a given user.
     *
     * Possible values include:
     *
     * | Value | Fetches revisions…
     * | - | -
     * | `1` | created by the user with an ID of 1.
     * | a [[User]] object | created by the user represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch revisions by the current user #}
     * {% set {elements-var} = {twig-method}
     *   .revisionCreator(currentUser)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch revisions by the current user
     * ${elements-var} = {php-method}
     *     ->revisionCreator(Auth::user())
     *     ->all();
     * ```
     */
    public function revisionCreator(mixed $value): static
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
