<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries;

use Closure;
use craft\elements\Entry;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Queries\Concerns\QueriesAuthors;
use CraftCms\Cms\Database\Queries\Concerns\QueriesEntryDates;
use CraftCms\Cms\Database\Queries\Concerns\QueriesEntryTypes;
use CraftCms\Cms\Database\Queries\Concerns\QueriesNestedElements;
use CraftCms\Cms\Database\Queries\Concerns\QueriesRef;
use CraftCms\Cms\Database\Queries\Concerns\QueriesSections;
use CraftCms\Cms\Database\Queries\Exceptions\QueryAbortedException;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Sections;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

final class EntryQuery extends ElementQuery
{
    use QueriesAuthors;
    use QueriesEntryDates;
    use QueriesEntryTypes;
    use QueriesNestedElements {
        cacheTags as nestedTraitCacheTags;
        fieldLayouts as nestedTraitFieldLayouts;
    }
    use QueriesRef;
    use QueriesSections;

    /**
     * {@inheritdoc}
     */
    protected array $defaultOrderBy = [
        'entries.postDate' => SORT_DESC,
        'elements.id' => SORT_DESC,
    ];

    protected function getFieldIdColumn(): string
    {
        return 'entries.fieldId';
    }

    protected function getPrimaryOwnerIdColumn(): string
    {
        return 'entries.primaryOwnerId';
    }

    /**
     * @var bool|null Whether to only return entries that the user has permission to view.
     *
     * @used-by editable()
     */
    public ?bool $editable = null;

    /**
     * @var bool|null Whether to only return entries that the user has permission to save.
     *
     * @used-by savable()
     */
    public ?bool $savable = null;

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
            $this->applyAuthParam($query, $query->editable, 'viewEntries', 'viewPeerEntries', 'viewPeerEntryDrafts');
            $this->applyAuthParam($query, $query->savable, 'saveEntries', 'savePeerEntries', 'savePeerEntryDrafts');
        });
    }

    protected function statusCondition(string $status): Closure
    {
        if (
            Cms::config()->staticStatuses &&
            in_array($status, [Entry::STATUS_LIVE, Entry::STATUS_PENDING, Entry::STATUS_EXPIRED])
        ) {
            return fn (Builder $query) => $query
                ->where('elements.enabled', true)
                ->where('elements_sites.enabled', true)
                ->where('entries.status', $status);
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
     * Sets the [[$editable]] property.
     *
     * @param  bool|null  $value  The property value (defaults to true)
     * @return static self reference
     *
     * @uses $editable
     */
    public function editable(?bool $value = true): self
    {
        $this->editable = $value;

        return $this;
    }

    /**
     * Sets the [[$savable]] property.
     *
     * @param  bool|null  $value  The property value (defaults to true)
     * @return self self reference
     *
     * @uses $savable
     */
    public function savable(?bool $value = true): self
    {
        $this->savable = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the entries’ statuses.
     *
     * Possible values include:
     *
     * | Value | Fetches entries…
     * | - | -
     * | `'live'` _(default)_ | that are live.
     * | `'pending'` | that are pending (enabled with a Post Date in the future).
     * | `'expired'` | that are expired (enabled with an Expiry Date in the past).
     * | `'disabled'` | that are disabled.
     * | `['live', 'pending']` | that are live or pending.
     * | `['not', 'live', 'pending']` | that are not live or pending.
     *
     * ---
     *
     * ```twig
     * {# Fetch disabled entries #}
     * {% set {elements-var} = {twig-method}
     *   .status('disabled')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch disabled entries
     * ${elements-var} = {element-class}::find()
     *     ->status('disabled')
     *     ->all();
     * ```
     */
    public function status(array|string|null $value): static
    {
        /** @var static */
        return parent::status($value);
    }

    /**
     * @throws QueryAbortedException
     */
    private function applyAuthParam(
        self $query,
        ?bool $value,
        string $permissionPrefix,
        string $peerPermissionPrefix,
        string $peerDraftPermissionPrefix,
    ): void {
        if ($value === null) {
            return;
        }

        $user = Auth::user();

        if (! $user) {
            throw new QueryAbortedException;
        }

        $sections = Sections::getAllSections();

        if ($sections->isEmpty()) {
            return;
        }

        $query->subQuery->where(function (Builder $query) use ($value, $peerDraftPermissionPrefix, $peerPermissionPrefix, $permissionPrefix, $user, $sections) {
            $partialAccessSections = [];

            foreach ($sections as $section) {
                if (! $user->can("$permissionPrefix:$section->uid")) {
                    continue;
                }

                $excludePeerEntries = $section->type !== SectionType::Single && ! $user->can("$peerPermissionPrefix:$section->uid");
                $excludePeerDrafts = $this->drafts !== false && ! $user->can("$peerDraftPermissionPrefix:$section->uid");

                if ($excludePeerEntries || $excludePeerDrafts) {
                    $partialAccessSections[] = $section->id;

                    $query->orWhere(function (Builder $query) use ($excludePeerDrafts, $user, $excludePeerEntries, $section) {
                        $query->where('entries.sectionId', $section->id);

                        if ($excludePeerEntries) {
                            $query->whereExists(
                                DB::table(Table::ENTRIES_AUTHORS, 'entries_authors')
                                    ->whereColumn('entries_authors.entryId', 'entries.id')
                                    ->where('entries_authors.authorId', $user->id)
                            );
                        }

                        if ($excludePeerDrafts) {
                            $query->where(function (Builder $query) use ($user) {
                                $query->whereNull('elements.draftId')
                                    ->orWhere('drafts.creatorId', $user->id);
                            });
                        }
                    });
                } else {
                    $fullyAuthorizedSectionIds[] = $section->id;
                }
            }

            if (! empty($fullyAuthorizedSectionIds)) {
                if (count($fullyAuthorizedSectionIds) === count($sections)) {
                    // They have access to everything
                    if (! $value) {
                        throw new QueryAbortedException;
                    }

                    return;
                }

                $query->orWhereIn('entries.sectionId', $fullyAuthorizedSectionIds);
            }

            // They don't have access to anything
            if (empty($partialAccessSections) && $value) {
                throw new QueryAbortedException;
            }
        }, boolean: $value ? 'and' : 'and not');
    }

    /**
     * {@inheritdoc}
     */
    protected function cacheTags(): array
    {
        $tags = [];

        // If the type is set, go with that instead of the section
        if ($this->typeId) {
            foreach ($this->typeId as $typeId) {
                $tags[] = "entryType:$typeId";
            }
        } elseif ($this->sectionId) {
            foreach (Arr::wrap($this->sectionId) as $sectionId) {
                $tags[] = "section:$sectionId";
            }
        }

        array_push($tags, ...$this->nestedTraitCacheTags());

        return $tags;
    }

    /**
     * {@inheritdoc}
     */
    protected function fieldLayouts(): Collection
    {
        $this->normalizeTypeId($this);
        $this->normalizeSectionId($this);

        $fieldLayouts = [];

        if ($this->typeId) {
            foreach ($this->typeId as $entryTypeId) {
                $entryType = EntryTypes::getEntryTypeById($entryTypeId);
                if ($entryType) {
                    $fieldLayouts[] = $entryType->getFieldLayout();
                }
            }

            return collect($fieldLayouts);
        }

        if ($this->sectionId) {
            foreach ($this->sectionId as $sectionId) {
                if ($section = Sections::getSectionById($sectionId)) {
                    foreach ($section->getEntryTypes() as $entryType) {
                        $fieldLayouts[] = $entryType->getFieldLayout();
                    }
                }
            }

            return collect($fieldLayouts);
        }

        return $this->nestedTraitFieldLayouts();
    }
}
