<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Concerns;

use craft\helpers\Db as DbHelper;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Arr;
use Illuminate\Database\Query\Builder;
use Tpetry\QueryExpressions\Language\Alias;

/**
 * @mixin \CraftCms\Cms\Database\Queries\ElementQuery
 *
 * @internal
 */
trait QueriesFields
{
    /**
     * @var mixed The element ID(s). Prefix IDs with `'not '` to exclude them.
     *
     * @used-by id()
     */
    public mixed $id = null;

    /**
     * @var mixed The element UID(s). Prefix UIDs with `'not '` to exclude them.
     *
     * @used-by uid()
     */
    public mixed $uid = null;

    /**
     * @var mixed The element ID(s) in the `elements_sites` table. Prefix IDs with `'not '` to exclude them.
     *
     * @used-by siteSettingsId()
     */
    public mixed $siteSettingsId = null;

    /**
     * @var bool|null Whether to return trashed (soft-deleted) elements.
     *                If this is set to `null`, then both trashed and non-trashed elements will be returned.
     *
     * @used-by trashed()
     */
    public ?bool $trashed = false;

    /**
     * @var mixed When the resulting elements must have been created.
     *
     * @used-by dateCreated()
     */
    public mixed $dateCreated = null;

    /**
     * @var mixed When the resulting elements must have been last updated.
     *
     * @used-by dateUpdated()
     */
    public mixed $dateUpdated = null;

    /**
     * @var mixed The title that resulting elements must have.
     *
     * @used-by title()
     */
    public mixed $title = null;

    /**
     * @var mixed The slug that resulting elements must have.
     *
     * @used-by slug()
     */
    public mixed $slug = null;

    /**
     * @var mixed The URI that the resulting element must have.
     *
     * @used-by uri()
     */
    public mixed $uri = null;

    /**
     * @var mixed The search term to filter the resulting elements by.
     *
     * See [Searching](https://craftcms.com/docs/5.x/system/searching.html) for supported syntax options.
     *
     * @used-by ElementQuery::search()
     */
    public mixed $search = null;

    /**
     * @var string|null The bulk element operation key that the resulting elements were involved in.
     *
     * @used-by ElementQuery::inBulkOp()
     */
    public ?string $inBulkOp = null;

    /**
     * @var mixed The reference code(s) used to identify the element(s).
     *
     * This property is set when accessing elements via their reference tags, e.g. `{entry:section/slug}`.
     *
     * @used-by ElementQuery::ref()
     */
    public mixed $ref = null;

    protected function initializeQueriesFields(): void
    {
        $this->subQuery->beforeQuery(function (Builder $query) {
            if ($this->id) {
                foreach (DbHelper::parseNumericParam('elements.id', $this->id) as $column => $values) {
                    $query->whereIn($column, Arr::wrap($values));
                }
            }

            if ($this->uid) {
                foreach (DbHelper::parseParam('elements.uid', $this->uid) as $column => $values) {
                    $query->whereIn($column, Arr::wrap($values));
                }
            }

            if ($this->siteSettingsId) {
                foreach (DbHelper::parseNumericParam('elements_sites.id', $this->siteSettingsId) as $column => $values) {
                    $query->whereIn($column, Arr::wrap($values));
                }
            }

            match ($this->trashed) {
                true => $query->whereNotNull('elements.dateDeleted'),
                false => $query->whereNull('elements.dateDeleted'),
                default => null,
            };

            if ($this->dateCreated) {
                $parsed = DbHelper::parseDateParam('elements.dateCreated', $this->dateCreated);

                $operator = $parsed[0];
                $column = $parsed[1];
                $value = $parsed[2] ?? null;

                if (is_null($value)) {
                    $value = $column;
                    $column = $operator;
                    $operator = '=';
                }

                $query->where($column, $operator, $value);
            }

            if ($this->dateUpdated) {
                $query->where(DbHelper::parseDateParam('elements.dateUpdated', $this->dateUpdated));
            }

            if (isset($this->title) && $this->title !== '' && $this->elementType::hasTitles()) {
                if (is_string($this->title)) {
                    $this->title = DbHelper::escapeCommas($this->title);
                }

                $query->where(DbHelper::parseParam('elements_sites.title', $this->title, '=', true));
            }

            if ($this->slug) {
                $query->where(DbHelper::parseParam('elements_sites.slug', $this->slug));
            }

            if ($this->uri) {
                $query->where(DbHelper::parseParam('elements_sites.uri', $this->uri, '=', true));
            }

            if ($this->inBulkOp) {
                $query
                    ->join(new Alias(Table::ELEMENTS_BULKOPS, 'elements_bulkops'), 'elements_bulkops.elementId', 'elements.id')
                    ->where('elements_bulkops.key', $this->inBulkOp);
            }
        });
    }

    /**
     * {@inheritdoc}
     *
     * @uses $id
     */
    public function id($value): static
    {
        $this->id = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @uses $uid
     */
    public function uid($value): static
    {
        $this->uid = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @uses $siteSettingsId
     */
    public function siteSettingsId($value): static
    {
        $this->siteSettingsId = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @uses $trashed
     */
    public function trashed(?bool $value = true): static
    {
        $this->trashed = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @uses $dateCreated
     */
    public function dateCreated(mixed $value): static
    {
        $this->dateCreated = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @uses $dateUpdated
     */
    public function dateUpdated(mixed $value): static
    {
        $this->dateUpdated = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @uses $title
     */
    public function title($value): static
    {
        $this->title = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @uses $slug
     */
    public function slug($value): static
    {
        $this->slug = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @uses $uri
     */
    public function uri($value): static
    {
        $this->uri = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @uses $search
     */
    public function search($value): static
    {
        $this->search = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @uses $inBulkOp
     */
    public function inBulkOp(?string $value): static
    {
        $this->inBulkOp = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @uses $ref
     */
    public function ref($value): static
    {
        $this->ref = $value;

        return $this;
    }
}
