<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries;

use Closure;
use craft\db\Query;
use craft\elements\Entry;
use craft\helpers\Db;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Sections;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Date;

final class EntryQuery extends ElementQuery
{
    public ?bool $withStructure = true;

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

    public function __construct(array $config = [])
    {
        // Default status
        if (! isset($config['status'])) {
            $config['status'] = [
                Entry::STATUS_LIVE,
            ];
        }

        parent::__construct(Entry::class, $config);

        $this->joinElementTable(Table::ENTRIES);

        $this->query->addSelect([
            'entries.sectionId as sectionId',
            'entries.fieldId as fieldId',
            'entries.primaryOwnerId as primaryOwnerId',
            'entries.typeId as typeId',
            'entries.postDate as postDate',
            'entries.expiryDate as expiryDate',
        ]);

        if (Cms::config()->staticStatuses) {
            $this->query->addSelect(['entries.status as status']);
        }

        $this->beforeQuery(function (self $query) {
            $this->_normalizeSectionId();
            $this->_applySectionIdParam($query);
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

        if ($value instanceof \CraftCms\Cms\Section\Data\Section) {
            // Special case for a single section, since we also want to capture the structure ID
            $this->sectionId = [$value->id];
            if ($value->structureId) {
                $this->structureId = $value->structureId;
            } else {
                $this->withStructure = false;
            }
        } elseif ($value === '*') {
            $this->sectionId = Sections::getAllSectionIds()->values()->all();
        } elseif (Db::normalizeParam($value, function ($item) {
            if (is_string($item)) {
                $item = Sections::getSectionByHandle($item);
            }

            return $item instanceof \CraftCms\Cms\Section\Data\Section ? $item->id : null;
        })) {
            $this->sectionId = $value;
        } else {
            $this->sectionId = new Query()
                ->select(['id'])
                ->from([\craft\db\Table::SECTIONS])
                ->where(Db::parseParam('handle', $value))
                ->column();
        }

        return $this;
    }

    protected function statusCondition(string $status): Closure
    {
        if (
            Cms::config()->staticStatuses &&
            in_array($status, [Entry::STATUS_LIVE, Entry::STATUS_PENDING, Entry::STATUS_EXPIRED])
        ) {
            return fn (Builder $query) => $query->where('elements.enabled', true)->where('elements_sites.enabled', true)->where('entries.status', $status);
        }

        // Always consider “now” to be the current time @ 59 seconds into the minute.
        // This makes entry queries more cacheable, since they only change once every minute (https://github.com/craftcms/cms/issues/5389),
        // while not excluding any entries that may have just been published in the past minute (https://github.com/craftcms/cms/issues/7853).
        $currentTime = Date::now()->endOfMinute();

        return match ($status) {
            Entry::STATUS_LIVE => fn (Builder $query) => $query
                ->where('elements.enabled', true)
                ->where('elements_sites.enabled', true)
                ->where('entries.postDate', '<=', $currentTime)
                ->where(function (Builder $query) use ($currentTime) {
                    $query->whereNull('entries.expiryDate')
                        ->orWhere('entries.expiryDate', '>', $currentTime);
                }),
            Entry::STATUS_PENDING => fn (Builder $query) => $query
                ->where('elements.enabled', true)
                ->where('elements_sites.enabled', true)
                ->where('entries.postDate', '>', $currentTime),
            Entry::STATUS_EXPIRED => fn (Builder $query) => $query
                ->where('elements.enabled', true)
                ->where('elements_sites.enabled', true)
                ->whereNotNull('entries.expiryDate')
                ->where('entries.expiryDate', '<=', $currentTime),
            default => parent::statusCondition($status),
        };
    }

    /**
     * Applies the 'sectionId' param to the query being prepared.
     */
    private function _applySectionIdParam(self $entryQuery): void
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
    private function _normalizeSectionId(): void
    {
        if (empty($this->sectionId)) {
            $this->sectionId = is_array($this->sectionId) ? [] : null;
        } elseif (is_numeric($this->sectionId)) {
            $this->sectionId = [$this->sectionId];
        } elseif (! is_array($this->sectionId) || ! Arr::isNumeric($this->sectionId)) {
            $this->sectionId = new Query()
                ->select(['id'])
                ->from([\craft\db\Table::SECTIONS])
                ->where(Db::parseNumericParam('id', $this->sectionId))
                ->column();
        }
    }
}
