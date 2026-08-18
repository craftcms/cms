<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Concerns\Entry;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Element\Queries\Exceptions\QueryAbortedException;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Query;
use Illuminate\Support\Facades\DB;

/**
 * @internal
 */
trait QueriesSections
{
    /**
     * @var mixed The section ID(s) that the resulting entries must be in.
     *            ---
     *            ```php
     *            // fetch entries in the News section
     *            $entries = \CraftCms\Cms\Entry\Elements\Entry::find()
     *            ->section('news')
     *            ->all();
     *            ```
     *            ```twig
     *            {# fetch entries in the News section #}
     *            {% set entries = entries()
     *            .section('news')
     *            .all() %}
     *            ```
     *
     * @used-by section()
     * @used-by sectionId()
     */
    public mixed $sectionId = null;

    protected function initQueriesSections(): void
    {
        $this->beforeQuery(function (EntryQuery $entryQuery) {
            $this->normalizeSectionId($entryQuery);

            if ($entryQuery->sectionId === []) {
                throw new QueryAbortedException;
            }

            $this->applySectionIdParam($entryQuery);
        });
    }

    /**
     * Narrows the query results based on the sections the entries belong to.
     *
     * Possible values include:
     *
     * | Value | Fetches entries…
     * | - | -
     * | `'foo'` | in a section with a handle of `foo`.
     * | `'not foo'` | not in a section with a handle of `foo`.
     * | `['foo', 'bar']` | in a section with a handle of `foo` or `bar`.
     * | `['not', 'foo', 'bar']` | not in a section with a handle of `foo` or `bar`.
     * | a [[Section|Section]] object | in a section represented by the object.
     * | `'*'` | in any section.
     *
     * ---
     *
     * ```twig
     * {# Fetch entries in the Foo section #}
     * {% set {elements-var} = {twig-method}
     *   .section('foo')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch entries in the Foo section
     * ${elements-var} = {php-method}
     *     ->section('foo')
     *     ->all();
     * ```
     *
     *
     * @uses $sectionId
     */
    public function section(mixed $value): static
    {
        // If the value is a section handle, swap it with the section
        if (is_string($value) && ($section = Sections::getSectionByHandle($value))) {
            $value = $section;
        }

        if ($value instanceof Section) {
            // Special case for a single section, since we also want to capture the structure ID
            $this->sectionId = [$value->id];
            if ($value->structureId) {
                $this->structureId = $value->structureId;
            } else {
                $this->withStructure = false;
            }
        } elseif ($value === '*') {
            $this->sectionId = Sections::getAllSectionIds()->values()->all();
        } elseif (Query::normalizeParam($value, function ($item) {
            if (is_string($item)) {
                $item = Sections::getSectionByHandle($item);
            }

            return $item instanceof Section ? $item->id : null;
        })) {
            $this->sectionId = $value;
        } else {
            $this->sectionId = DB::table(Table::SECTIONS)
                ->whereParam('handle', $value)
                ->pluck('id')
                ->all();
        }

        return $this;
    }

    /**
     * Narrows the query results based on the sections the entries belong to, per the sections’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches entries…
     * | - | -
     * | `1` | in a section with an ID of 1.
     * | `'not 1'` | not in a section with an ID of 1.
     * | `[1, 2]` | in a section with an ID of 1 or 2.
     * | `['not', 1, 2]` | not in a section with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch entries in the section with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *   .sectionId(1)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch entries in the section with an ID of 1
     * ${elements-var} = {php-method}
     *     ->sectionId(1)
     *     ->all();
     * ```
     *
     *
     * @uses $sectionId
     */
    public function sectionId(mixed $value): static
    {
        $this->sectionId = $value;

        return $this;
    }

    /**
     * Applies the 'sectionId' param to the query being prepared.
     */
    /** @param EntryQuery<Entry> $entryQuery */
    private function applySectionIdParam(EntryQuery $entryQuery): void
    {
        if (! $entryQuery->sectionId) {
            return;
        }

        $entryQuery->whereIn('entries.sectionId', $entryQuery->sectionId);

        // Should we set the structureId param?
        if (
            $entryQuery->withStructure &&
            ! isset($entryQuery->structureId) &&
            count($entryQuery->sectionId) === 1
        ) {
            $section = Sections::getSectionById(reset($entryQuery->sectionId));
            if ($section && $section->type === SectionType::Structure) {
                $entryQuery->structureId = $section->structureId;
            } else {
                $entryQuery->withStructure = false;
            }
        }
    }

    /**
     * Normalizes the sectionId param to an array of IDs or null
     */
    /** @param EntryQuery<Entry> $entryQuery */
    private function normalizeSectionId(EntryQuery $entryQuery): void
    {
        $entryQuery->sectionId = match (true) {
            empty($entryQuery->sectionId) => is_array($entryQuery->sectionId) ? [] : null,
            is_numeric($entryQuery->sectionId) => [$entryQuery->sectionId],
            ! is_array($entryQuery->sectionId) || ! Arr::isNumeric($entryQuery->sectionId) => DB::table(Table::SECTIONS)
                ->whereNumericParam('id', $entryQuery->sectionId)
                ->pluck('id')
                ->all(),
            default => $entryQuery->sectionId,
        };
    }
}
