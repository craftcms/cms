<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Concerns;

use craft\helpers\Db as DbHelper;
use CraftCms\Cms\Database\Queries\EntryQuery;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Sections;
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
     *            $entries = \craft\elements\Entry::find()
     *            ->section('news')
     *            ->all();
     *            ```
     *            ```twig
     *            {# fetch entries in the News section #}
     *            {% set entries = craft.entries()
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
            $this->normalizeSectionId();
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
     * @param  mixed  $value  The property value
     * @return static self reference
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
        } elseif (DbHelper::normalizeParam($value, function ($item) {
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
     * Applies the 'sectionId' param to the query being prepared.
     */
    private function applySectionIdParam(EntryQuery $entryQuery): void
    {
        if (! $this->sectionId) {
            return;
        }

        $entryQuery->subQuery->where('entries.sectionId', $this->sectionId);

        // Should we set the structureId param?
        if (
            $entryQuery->withStructure !== false &&
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
    private function normalizeSectionId(): void
    {
        $this->sectionId = match (true) {
            empty($this->sectionId) => is_array($this->sectionId) ? [] : null,
            is_numeric($this->sectionId) => [$this->sectionId],
            ! is_array($this->sectionId) || ! Arr::isNumeric($this->sectionId) => DB::table(Table::SECTIONS)
                ->whereNumericParam('id', $this->sectionId)
                ->value('id'),
            default => $this->sectionId,
        };
    }
}
