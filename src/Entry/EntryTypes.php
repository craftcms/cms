<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry;

use Craft;
use craft\base\MemoizableArray;
use craft\errors\EntryTypeNotFoundException;
use craft\helpers\AdminTable;
use craft\helpers\Cp;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Jobs\ResaveElements;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Events\ApplyingDeleteEntryType;
use CraftCms\Cms\Entry\Events\DeletingEntryType;
use CraftCms\Cms\Entry\Events\EntryTypeDeleted;
use CraftCms\Cms\Entry\Events\EntryTypeSaved;
use CraftCms\Cms\Entry\Events\SavingEntryType;
use CraftCms\Cms\Entry\Models\EntryType as EntryTypeModel;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Throwable;
use Tpetry\QueryExpressions\Language\Alias;
use yii\base\InvalidConfigException;
use yii\helpers\Markdown;

#[Singleton]
final class EntryTypes
{
    /**
     * @var bool Whether entries should be resaved after an entry type has been updated.
     *
     * ::: warning
     * Don’t disable this unless you know what you’re doing, as entries won’t reflect entry type changes until
     * they’ve been resaved. (You can resave entries manually by running the `resave/entries` console command.)
     * :::
     */
    public bool $autoResaveEntries = true;

    /**
     * @var MemoizableArray<EntryType>|null
     *
     * @see entryTypes()
     */
    private ?MemoizableArray $entryTypes = null;

    public function __construct(
        private readonly ProjectConfig $projectConfig,
    ) {}

    /**
     * Returns a section’s entry types.
     *
     * ---
     *
     * ```php
     * $entryTypes = Craft::$app->entries->getEntryTypesBySectionId(1);
     * ```
     *
     *
     * @return \Illuminate\Support\Collection<EntryType>
     */
    public function getEntryTypesBySectionId(int $sectionId): Collection
    {
        return DB::table(Table::SECTIONS_ENTRYTYPES)
            ->select([new Alias('typeId', 'id'), 'name', 'description', 'handle'])
            ->where('sectionId', $sectionId)
            ->orderBy('sortOrder')
            ->get()
            ->map(fn (object $entryType) => $this->getEntryType((array) $entryType))
            ->filter()
            ->values();
    }

    /**
     * Returns a memoizable array of all entry types.
     *
     * @return MemoizableArray<EntryType>
     */
    private function entryTypes(): MemoizableArray
    {
        if (isset($this->entryTypes)) {
            return $this->entryTypes;
        }

        /** @var MemoizableArray<EntryType> $entryTypes */
        $entryTypes = new MemoizableArray(
            $this->_createEntryTypeQuery()->get()->all(),
            function (object $result) {
                $result->titleTranslationMethod = TranslationMethod::from($result->titleTranslationMethod);
                $result->slugTranslationMethod = TranslationMethod::from($result->slugTranslationMethod);
                $result->color = $result->color ? Color::from($result->color) : null;

                return new EntryType($result);
            },
        );

        return $this->entryTypes = $entryTypes;
    }

    private function _createEntryTypeQuery(): Builder
    {
        return DB::table(Table::ENTRYTYPES)
            ->select([
                'id',
                'fieldLayoutId',
                'name',
                'description',
                'icon',
                'color',
                'handle',
                'hasTitleField',
                'titleTranslationMethod',
                'titleTranslationKeyFormat',
                'titleFormat',
                'allowLineBreaksInTitles',
                'slugTranslationMethod',
                'slugTranslationKeyFormat',
                'showStatusField',
                'showSlugField',
                'uiLabelFormat',
                'uid',
            ])
            ->whereNull('dateDeleted');
    }

    /**
     * Returns all entry types.
     *
     * ---
     *
     * ```php
     * $entryTypes = Craft::$app->entries->getAllEntryTypes();
     * ```
     *
     * @return Collection<EntryType>
     */
    public function getAllEntryTypes(): Collection
    {
        return collect($this->entryTypes()->all());
    }

    /**
     * Returns an entry type by its ID.
     *
     * ---
     *
     * ```php
     * $entryType = Craft::$app->entries->getEntryTypeById(1);
     * ```
     */
    public function getEntryTypeById(int $entryTypeId, bool $withTrashed = false): ?EntryType
    {
        $entryType = $this->entryTypes()->firstWhere('id', $entryTypeId);

        if (! $entryType && $withTrashed) {
            $record = $this->getEntryTypeModel($entryTypeId, true);
            if ($record->exists) {
                return new EntryType(Arr::only($record->toArray(), [
                    'id',
                    'fieldLayoutId',
                    'name',
                    'handle',
                    'description',
                    'hasTitleField',
                    'titleTranslationMethod',
                    'titleTranslationKeyFormat',
                    'titleFormat',
                    'uid',
                ]));
            }
        }

        return $entryType;
    }

    public function getEntryTypeByUid(string $uid): ?EntryType
    {
        return $this->entryTypes()->firstWhere('uid', $uid);
    }

    /**
     * Returns an entry type by its handle.
     *
     * ---
     *
     * ```php
     * $entryType = Craft::$app->entries->getEntryTypeByHandle('article');
     * ```
     */
    public function getEntryTypeByHandle(string $entryTypeHandle): ?EntryType
    {
        return $this->entryTypes()->firstWhere('handle', $entryTypeHandle, true);
    }

    /**
     * Returns an entry type by its usage config.
     *
     * @param  EntryType|int|string|array{id?:int,uid?:string,name?:string,handle?:string}  $entryType
     */
    public function getEntryType(mixed $entryType): ?EntryType
    {
        if ($entryType instanceof EntryType) {
            return $entryType;
        }

        if (is_numeric($entryType)) {
            return $this->getEntryTypeById($entryType);
        }

        if (is_string($entryType)) {
            try {
                $config = Json::decode($entryType);
            } catch (InvalidArgumentException) {
                return $this->getEntryTypeByUid($entryType);
            }
        } else {
            $config = $entryType;
        }

        if (isset($config['id'])) {
            $entryType = $this->getEntryTypeById($config['id']);
        } elseif (isset($config['uid'])) {
            $entryType = $this->getEntryTypeByUid($config['uid']);
        } else {
            throw new InvalidArgumentException('Invalid entry type.');
        }

        if (! $entryType) {
            return null;
        }

        if (isset($config['name']) || isset($config['handle']) || isset($config['description']) || isset($config['group'])) {
            $original = $entryType;
            $entryType = clone $original;
            $entryType->name = $config['name'] ?? $original->name;
            $entryType->handle = $config['handle'] ?? $original->handle;
            $entryType->description = $config['description'] ?? $original->description;
            $entryType->group = $config['group'] ?? null;
            $entryType->original = $original;
        }

        return $entryType;
    }

    /**
     * Saves an entry type.
     *
     * @param  EntryType  $entryType  The entry type to be saved
     * @return bool Whether the entry type was saved successfully
     *
     * @throws EntryTypeNotFoundException if $entryType->id is invalid
     * @throws Throwable if reasons
     */
    public function saveEntryType(EntryType $entryType): bool
    {
        $isNewEntryType = ! $entryType->id;

        event(new SavingEntryType($entryType, $isNewEntryType));

        $entryType->hasTitleField = $entryType->getFieldLayout()->isFieldIncluded('title');

        if ($isNewEntryType && ! $entryType->uid) {
            $entryType->uid = Str::uuid()->toString();
        }

        $this->projectConfig->set(
            path: ProjectConfig::PATH_ENTRY_TYPES.'.'.$entryType->uid,
            value: $entryType->getConfig(),
            message: "Save entry type “{$entryType->handle}”",
        );

        if ($isNewEntryType) {
            $entryType->id = DB::table(Table::ENTRYTYPES)->idByUid($entryType->uid);
        }

        return true;
    }

    /**
     * Handle entry type change
     */
    public function handleChangedEntryType(ConfigEvent $event): void
    {
        $entryTypeUid = $event->tokenMatches[0];
        $data = $event->newValue;

        // Make sure fields are processed
        ProjectConfigHelper::ensureAllSitesProcessed();
        ProjectConfigHelper::ensureAllFieldsProcessed();

        $entryTypeModel = $this->getEntryTypeModel($entryTypeUid, true);

        DB::beginTransaction();

        try {
            $isNewEntryType = ! $entryTypeModel->exists;

            $entryTypeModel->name = $data['name'];
            $entryTypeModel->handle = $data['handle'];
            $entryTypeModel->icon = $data['icon'] ?? null;
            $entryTypeModel->color = $data['color'] ?? null;
            $entryTypeModel->hasTitleField = $data['hasTitleField'];
            $entryTypeModel->titleTranslationMethod = $data['titleTranslationMethod'] ?? '';
            $entryTypeModel->titleTranslationKeyFormat = $data['titleTranslationKeyFormat'] ?? null;
            $entryTypeModel->titleFormat = $data['titleFormat'];
            $entryTypeModel->allowLineBreaksInTitles = $data['allowLineBreaksInTitles'] ?? false;
            $entryTypeModel->uiLabelFormat = $data['uiLabelFormat'] ?? '{title}';
            $entryTypeModel->showSlugField = $data['showSlugField'] ?? true;
            $entryTypeModel->slugTranslationMethod = $data['slugTranslationMethod'] ?? Field::TRANSLATION_METHOD_SITE;
            $entryTypeModel->slugTranslationKeyFormat = $data['slugTranslationKeyFormat'] ?? null;
            $entryTypeModel->showStatusField = $data['showStatusField'] ?? true;
            $entryTypeModel->uid = $entryTypeUid;
            $entryTypeModel->description = $data['description'] ?? null;

            if (! empty($data['fieldLayouts'])) {
                // Save the field layout
                $layout = FieldLayout::createFromConfig(reset($data['fieldLayouts']));
                $layout->id = $entryTypeModel->fieldLayoutId;
                $layout->type = Entry::class;
                $layout->uid = key($data['fieldLayouts']);
                Fields::saveLayout($layout, false);
                $entryTypeModel->fieldLayoutId = $layout->id;
            } elseif ($entryTypeModel->fieldLayoutId) {
                // Delete the field layout
                Fields::deleteLayoutById($entryTypeModel->fieldLayoutId);
                $entryTypeModel->fieldLayoutId = null;
            }

            $resaveEntries = (
                $entryTypeModel->handle !== $entryTypeModel->getOriginal('handle') ||
                $entryTypeModel->hasTitleField != $entryTypeModel->getOriginal('hasTitleField') ||
                $entryTypeModel->titleFormat !== $entryTypeModel->getOriginal('titleFormat') ||
                $entryTypeModel->fieldLayoutId != $entryTypeModel->getOriginal('fieldLayoutId')
            );

            // Save the entry type
            $wasTrashed = (bool) $entryTypeModel->dateDeleted;
            if ($wasTrashed) {
                $entryTypeModel->dateDeleted = null;
                $resaveEntries = true;
            }

            $entryTypeModel->save();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        // Clear caches
        $this->refreshEntryTypes();

        if ($wasTrashed) {
            // Restore the entries at the end of the request in case the section isn't restored yet
            // (see https://github.com/craftcms/cms/issues/15787)
            Event::listen(RequestHandled::class, function () use ($entryTypeModel) {
                $entries = Entry::find()
                    ->typeId($entryTypeModel->id)
                    ->drafts(null)
                    ->draftOf(false)
                    ->status(null)
                    ->trashed()
                    ->site('*')
                    ->unique()
                    ->where('entries.deletedWithEntryType', true)
                    ->get();

                /** @var Entry[][] $entriesBySection */
                $entriesBySection = $entries->groupBy('sectionId')->all();
                foreach ($entriesBySection as $sectionEntries) {
                    try {
                        Craft::$app->getElements()->restoreElements($sectionEntries);
                    } catch (InvalidConfigException) {
                        // the section probably wasn't restored
                    }
                }
            });
        }

        /** @var EntryType $entryType */
        $entryType = $this->getEntryTypeById($entryTypeModel->id);

        if (! $isNewEntryType && $resaveEntries && $this->autoResaveEntries) {
            // Re-save the entries of this type
            dispatch(new ResaveElements(
                elementType: Entry::class,
                criteria: [
                    'typeId' => $entryType->id,
                    'siteId' => '*',
                    'unique' => true,
                    'status' => null,
                ],
                description: I18N::prep('Resaving {type} entries', [
                    'type' => $entryType->name,
                ]),
            ));
        }

        event(new EntryTypeSaved($entryType, $isNewEntryType));

        // Invalidate entry caches
        Craft::$app->getElements()->invalidateCachesForElementType(Entry::class);
    }

    /**
     * Deletes an entry type by its ID.
     *
     * ---
     *
     * ```php
     * $success = Craft::$app->entries->deleteEntryTypeById(1);
     * ```
     *
     *
     * @return bool Whether the entry type was deleted successfully
     *
     * @throws Throwable if reasons
     */
    public function deleteEntryTypeById(int $entryTypeId): bool
    {
        $entryType = $this->getEntryTypeById($entryTypeId);

        if (! $entryType) {
            return false;
        }

        return $this->deleteEntryType($entryType);
    }

    /**
     * Deletes an entry type.
     *
     * ---
     *
     * ```php
     * $success = Craft::$app->entries->deleteEntryType($entryType);
     * ```
     *
     *
     * @return bool Whether the entry type was deleted successfully
     *
     * @throws Throwable if reasons
     */
    public function deleteEntryType(EntryType $entryType): bool
    {
        event(new DeletingEntryType($entryType));

        $this->projectConfig->remove(
            path: ProjectConfig::PATH_ENTRY_TYPES.'.'.$entryType->uid,
            message: "Delete the “{$entryType->handle}” entry type",
        );

        return true;
    }

    /**
     * Handle an entry type getting deleted
     */
    public function handleDeletedEntryType(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];
        $entryTypeModel = $this->getEntryTypeModel($uid);

        if (! $entryTypeModel->id) {
            return;
        }

        /** @var EntryType $entryType */
        $entryType = $this->getEntryTypeById($entryTypeModel->id);

        event(new ApplyingDeleteEntryType($entryType));

        DB::beginTransaction();

        try {
            // Delete the entries
            $condition = fn (Builder $query) => $query->whereNull([
                'elements.canonicalId',
                'elements.revisionId',
                'elements.dateDeleted',
            ]);

            DB::table(Table::ELEMENTS, 'elements')
                ->whereIn(
                    'elements.id',
                    DB::table(Table::ENTRIES, 'entries')
                        ->where('entries.typeId', $entryType->id)
                        ->select('entries.id'),
                )
                ->where($condition)
                ->softDelete();

            DB::table(Table::ENTRIES, 'entries')
                ->whereIn(
                    'entries.id',
                    DB::table(Table::ELEMENTS, 'elements')
                        ->where($condition)
                        ->select('elements.id'),
                )
                ->where('entries.typeId', $entryType->id)
                ->update([
                    'deletedWithEntryType' => true,
                ]);

            // Delete the field layout
            if ($entryTypeModel->fieldLayoutId) {
                Fields::deleteLayoutById($entryTypeModel->fieldLayoutId);
            }

            // Delete the entry type.
            DB::table(Table::ENTRYTYPES)->softDelete($entryTypeModel->id);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        // Clear caches
        $this->refreshEntryTypes();

        event(new EntryTypeDeleted($entryType));

        // Invalidate entry caches
        Craft::$app->getElements()->invalidateCachesForElementType(Entry::class);
    }

    /**
     * Refreshes the internal entry type cache.
     */
    public function refreshEntryTypes(): void
    {
        $this->entryTypes = null;

        // Sections cache entry types internally, so ensure those get refreshed as well.
        Sections::refreshSections();
    }

    /**
     * Returns data for the Entry Types index page in the control panel.
     *
     *
     * @internal
     */
    public function getTableData(
        int $page,
        int $limit,
        ?string $searchTerm = null,
        string $orderBy = 'name',
        int $sortDir = SORT_ASC,
    ): array {
        [$results, $total] = $this->prepTableData(
            query: $this->_createEntryTypeQuery(),
            page: $page,
            limit: $limit,
            searchTerm: $searchTerm,
            orderBy: $orderBy,
            sortDir: $sortDir,
        );

        /** @var Collection<EntryType> $entryTypes */
        $entryTypes = $results->map(fn (object $result) => $this->entryTypes()->firstWhere('id',
            $result->id))->filter()->values();

        $tableData = [];
        $usages = $this->allEntryTypeUsages();

        foreach ($entryTypes as $entryType) {
            $label = Html::encode($entryType->getUiLabel());
            $chipCellContent = Html::beginTag('div', ['class' => 'inline-chips']).
                Cp::chipHtml($entryType, [
                    'labelHtml' => Html::a($label, $entryType->getCpEditUrl(), [
                        'class' => ['chip-label', 'cell-bold'],
                    ]),
                ]);
            if ($entryType->description) {
                $chipCellContent .= Html::tag('span',
                    Html::decodeDoubles(Markdown::process(Html::encodeInvalidTags(Html::encode($entryType->description)),
                        'gfm-comment')),
                    ['class' => 'info']);
            }
            $chipCellContent .= Html::endTag('div');

            $tableData[] = [
                'id' => $entryType->id,
                'title' => $label,
                'chip' => $chipCellContent,
                'handle' => $entryType->handle,
                'usages' => Cp::componentPreviewHtml($usages[$entryType->id] ?? []),
            ];
        }

        $pagination = AdminTable::paginationLinks($page, $total, $limit);

        return [$pagination, $tableData];
    }

    /**
     * Returns query results needed for the VueAdminTable accounting for the pagination, search terms and sorting options.
     *
     *
     * @return array{0: Collection, 1: int}
     */
    private function prepTableData(
        Builder $query,
        int $page,
        int $limit,
        ?string $searchTerm,
        string $orderBy = 'name',
        int $sortDir = SORT_ASC,
    ): array {
        $searchTerm = $searchTerm ? trim($searchTerm) : $searchTerm;

        $offset = ($page - 1) * $limit;
        $query = $query->orderBy($orderBy, $sortDir === SORT_DESC ? 'desc' : 'asc');

        if ($searchTerm !== null && $searchTerm !== '') {
            $searchParams = $this->getSearchParams($searchTerm);
            if (! empty($searchParams)) {
                $query->where(function (Builder $query) use ($searchParams) {
                    foreach ($searchParams as $param) {
                        $query->orWhere($param[0], $param[1], $param[2]);
                    }
                });
            }
        }

        $total = $query->count();

        $query->limit($limit);
        $query->offset($offset);

        return [$query->get(), $total];
    }

    /**
     * @return array<int,array<Section|ElementContainerFieldInterface>>
     */
    private function allEntryTypeUsages(): array
    {
        $usages = [];

        // Sections
        foreach (Sections::getAllSections() as $section) {
            foreach ($section->getEntryTypes() as $entryType) {
                $usages[$entryType->id][] = $section;
            }
        }

        // Fields
        foreach (Fields::getNestedEntryFieldTypes() as $type) {
            /** @var ElementContainerFieldInterface[] $fields */
            $fields = Fields::getFieldsByType($type);
            foreach ($fields as $field) {
                foreach ($field->getFieldLayoutProviders() as $provider) {
                    if ($provider instanceof EntryType) {
                        $usages[$provider->id][] = $field;
                    }
                }
            }
        }

        return $usages;
    }

    /**
     * Returns the sql expression to be used in the 'where' param for the query.
     */
    private function getSearchParams(string $term): array
    {
        $searchParams = ['name', 'handle'];
        $searchQueries = [];

        if ($term !== '') {
            foreach ($searchParams as $param) {
                $searchQueries[] = [$param, 'like', '%'.$term.'%'];
            }
        }

        return $searchQueries;
    }

    /**
     * Gets an entry type's record by uid.
     *
     * @param  bool  $withTrashed  Whether to include trashed entry types in search
     */
    private function getEntryTypeModel(int|string $idOrUid, bool $withTrashed = false): EntryTypeModel
    {
        return EntryTypeModel::query()
            ->when($withTrashed, fn (EloquentBuilder $query) => $query->withTrashed())
            ->when(
                is_numeric($idOrUid),
                fn (EloquentBuilder $query) => $query->where('id', $idOrUid),
                fn (EloquentBuilder $query) => $query->where('uid', $idOrUid),
            )
            ->first() ?? new EntryTypeModel;
    }
}
