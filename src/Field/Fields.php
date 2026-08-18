<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Component\Contracts\Iconic;
use CraftCms\Cms\Component\Exceptions\MissingComponentException;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Database\Expressions\FixedOrderExpression;
use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Data\FieldMergeResult;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\Field\Events\CompatibleFieldTypesResolving;
use CraftCms\Cms\Field\Events\FieldCachesInvalidated;
use CraftCms\Cms\Field\Events\FieldDeleted;
use CraftCms\Cms\Field\Events\FieldDeleting;
use CraftCms\Cms\Field\Events\FieldLayoutDeleted;
use CraftCms\Cms\Field\Events\FieldLayoutDeleting;
use CraftCms\Cms\Field\Events\FieldLayoutSaved;
use CraftCms\Cms\Field\Events\FieldLayoutSaving;
use CraftCms\Cms\Field\Events\FieldSaveApplying;
use CraftCms\Cms\Field\Events\FieldSaved;
use CraftCms\Cms\Field\Events\FieldSaving;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Missing as MissingLayoutElement;
use CraftCms\Cms\FieldLayout\Models\FieldLayout as FieldLayoutModel;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\Facades\ProjectConfig as ProjectConfigFacade;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Json as JsonHelper;
use CraftCms\Cms\Support\MemoizableArray;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Support\Str;
use Exception;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use PDO;
use Throwable;

use function CraftCms\Cms\t;

/**
 * @phpstan-type FieldConfig array{
 *     name:string|null,
 *     handle:string|null,
 *     instructions:string|null,
 *     searchable?:bool,
 *     translationMethod:string,
 *     translationKeyFormat:string|null,
 *     type:string,
 *     settings?:array<array-key, mixed>
 * }
 * @phpstan-type PaginationData array{
 *     total:int,
 *     per_page:int,
 *     current_page:int,
 *     last_page:int,
 *     next_page_url:string|null,
 *     prev_page_url:string|null,
 *     from:int|null,
 *     to:int|null
 * }
 * @phpstan-type FieldTableRow array{
 *     id:int|null,
 *     title:string,
 *     translatable:string|false,
 *     searchable:bool,
 *     url:string|null,
 *     handle:string|null,
 *     type:array{isMissing:bool, label:string|null, icon:array{name:string, family:string}|null},
 *     usages:string|null
 * }
 */
#[Singleton]
class Fields
{
    /**
     * @var string The active field context
     */
    public string $fieldContext = 'global';

    /**
     * @var MemoizableArray<FieldInterface>|null
     *
     * @see _fields()
     */
    private ?MemoizableArray $_fields = null;

    /**
     * @var MemoizableArray<FieldLayout>|null
     *
     * @see _layouts()
     */
    private ?MemoizableArray $_layouts = null;

    /** @var array<string, FieldInterface> */
    private array $_savingFields = [];

    /**
     * @var array<string,bool>|null Memoized map of all known custom field handles.
     */
    private ?array $_allFieldHandles = null;

    /**
     * @var array<string,bool>|null Memoized map of all generated field handles.
     */
    private ?array $_allGeneratedFieldHandles = null;

    public function __construct(
        private readonly ElementCaches $elementCaches,
        private readonly FieldTypes $fieldTypes,
        private readonly NestedEntryFieldTypes $nestedEntryFieldTypes,
    ) {}

    // Handle Registry
    // -------------------------------------------------------------------------

    /**
     * Returns a map of all known custom field handles (including layout-overridden handles).
     *
     * @return array<string,bool>
     */
    public function allFieldHandles(): array
    {
        if ($this->_allFieldHandles !== null) {
            return $this->_allFieldHandles;
        }

        $handles = [];

        foreach ($this->getAllFields(false) as $field) {
            $handles[$field->handle] = true;
        }

        foreach ($this->getAllLayouts() as $layout) {
            foreach ($layout->getCustomFields() as $field) {
                $handles[$field->handle] = true;
            }
        }

        return $this->_allFieldHandles = $handles;
    }

    /**
     * Returns a map of all generated field handles from all layouts.
     *
     * @return array<string,bool>
     */
    public function allGeneratedFieldHandles(): array
    {
        if ($this->_allGeneratedFieldHandles !== null) {
            return $this->_allGeneratedFieldHandles;
        }

        $handles = [];

        foreach ($this->getAllLayouts() as $layout) {
            foreach ($layout->getGeneratedFields() as $generatedField) {
                if (! empty($generatedField['handle'])) {
                    $handles[$generatedField['handle']] = true;
                }
            }
        }

        return $this->_allGeneratedFieldHandles = $handles;
    }

    /**
     * Returns whether the given handle belongs to a custom field.
     */
    public function isFieldHandle(string $handle): bool
    {
        return isset($this->allFieldHandles()[$handle]);
    }

    /**
     * Returns whether the given handle belongs to a generated field.
     */
    public function isGeneratedFieldHandle(string $handle): bool
    {
        return isset($this->allGeneratedFieldHandles()[$handle]);
    }

    /**
     * Returns whether the given handle belongs to any known field (custom or generated).
     */
    public function isKnownFieldHandle(string $handle): bool
    {
        if ($this->isFieldHandle($handle)) {
            return true;
        }

        return $this->isGeneratedFieldHandle($handle);
    }

    /**
     * Invalidates all memoized field and layout caches including handle maps.
     *
     * Call this whenever fields or layouts change, without writing a new field version to the database.
     */
    public function invalidateCaches(): void
    {
        $this->_fields = null;
        $this->_layouts = null;
        $this->_allFieldHandles = null;
        $this->_allGeneratedFieldHandles = null;

        event(new FieldCachesInvalidated);
    }

    // Fields
    // -------------------------------------------------------------------------

    public function getFieldContext(): string
    {
        return $this->fieldContext;
    }

    public function setFieldContext(string $fieldContext): void
    {
        $this->fieldContext = $fieldContext;
    }

    /**
     * Returns all available field type classes.
     *
     * @return Collection<int, class-string<FieldInterface>>
     */
    public function getAllFieldTypes(): Collection
    {
        return $this->fieldTypes->types();
    }

    /**
     * Returns all field types that have a column in the content table.
     *
     * @return Collection<int, class-string<FieldInterface>>
     */
    public function getFieldTypesWithContent(): Collection
    {
        return $this->getAllFieldTypes()
            ->filter(fn (string $class) => $class::dbType() !== null)
            ->values();
    }

    /**
     * Returns all field types whose column types are considered compatible with a given field.
     *
     * @param  FieldInterface  $field  The current field to base compatible fields on
     * @param  bool  $includeCurrent  Whether $field's class should be included
     * @return Collection<int, class-string<FieldInterface>>
     */
    public function getCompatibleFieldTypes(FieldInterface $field, bool $includeCurrent = true): Collection
    {
        // If the field has any validation errors and has an ID, swap it with the saved field
        if (! $field->getIsNew() && $field->errors()->isNotEmpty()) {
            $field = $this->getFieldById($field->id);
        }

        $types = new Collection;
        $dbType = $field::dbType();

        if (is_string($dbType)) {
            foreach ($this->getAllFieldTypes() as $class) {
                if (
                    ($includeCurrent || $class !== $field::class) &&
                    $this->areFieldTypesCompatible($field::class, $class)
                ) {
                    $types->add($class);
                }
            }
        }

        // Make sure the current field class is in there if it's supposed to be
        if ($includeCurrent && $types->doesntContain($field::class)) {
            $types->add($field::class);
        }

        event($event = new CompatibleFieldTypesResolving($field, $types));

        return $event->compatibleTypes;
    }

    /**
     * Returns whether the two given field types are considered compatible with each other.
     *
     * @param  class-string<FieldInterface>  $fieldA
     * @param  class-string<FieldInterface>  $fieldB
     */
    public function areFieldTypesCompatible(string $fieldA, string $fieldB): bool
    {
        if ($fieldA === $fieldB) {
            return true;
        }

        $dbTypeA = $fieldA::dbType();
        if (! is_string($dbTypeA)) {
            return false;
        }

        $dbTypeB = $fieldB::dbType();
        if (! is_string($dbTypeB)) {
            return false;
        }

        return Query::areColumnTypesCompatible($dbTypeA, $dbTypeB);
    }

    /**
     * Returns all field types which manage nested entries.
     *
     * @return Collection<int, class-string<ElementContainerFieldInterface>> The field type classes which manage nested entries
     */
    public function getNestedEntryFieldTypes(): Collection
    {
        return $this->nestedEntryFieldTypes->types();
    }

    /**
     * Returns all available relational field type classes.
     *
     * @return Collection<int, class-string<BaseRelationField>> The available relational field type classes
     */
    public function getRelationalFieldTypes(): Collection
    {
        return $this->getAllFieldTypes()->filter(
            fn (string $class) => is_subclass_of($class, BaseRelationField::class),
        );
    }

    /**
     * Creates a field with a given config.
     *
     * @template T of FieldInterface
     *
     * @param  class-string<T>|array{type:string, id?:int|string, uid?:string, ...}  $config  The field’s class name, or its config, with a `type` value and optionally a `settings` value
     * @return ($config is string ? T : FieldInterface)
     */
    public function createField(mixed $config): FieldInterface
    {
        if (is_string($config)) {
            $config = ['type' => $config];
        }

        if (! empty($config['id']) && empty($config['uid']) && is_numeric($config['id'])) {
            $uid = DB::table(Table::FIELDS)->uidById($config['id']);
            $config['uid'] = $uid;
        }

        try {
            $field = ComponentHelper::createComponent($config, FieldInterface::class);
        } catch (MissingComponentException $e) {
            $config['errorMessage'] = $e->getMessage();
            $config['expectedType'] = $config['type'];
            unset($config['type']);

            $field = new MissingField($config);
        }

        return $field;
    }

    /**
     * Returns a memoizable array of fields.
     *
     * @param  string|string[]|false|null  $context  The field context(s) to fetch fields from. Defaults to [[\craft\services\Fields::$fieldContext]].
     *                                               Set to `false` to get all fields regardless of context.
     * @return MemoizableArray<FieldInterface>
     */
    private function _fields(mixed $context = null): MemoizableArray
    {
        $context ??= $this->fieldContext;

        if (! Cms::isInstalled()) {
            return new MemoizableArray([], fn () => []);
        }

        $this->_fields ??= new MemoizableArray(
            $this->_createFieldQuery()->get()->all(),
            fn (object $config) => $this->createField((array) $config),
        );

        if ($context === false) {
            return $this->_fields;
        }

        if (is_array($context)) {
            return $this->_fields->whereIn('context', $context, true);
        }

        return $this->_fields->where('context', $context, true);
    }

    /**
     * Returns all fields within a field context(s).
     *
     * @param  string|string[]|false|null  $context  The field context(s) to fetch fields from. Defaults to [[\craft\services\Fields::$fieldContext]].
     *                                               Set to `false` to get all fields regardless of context.
     * @return Collection<int, FieldInterface> The fields
     */
    public function getAllFields(mixed $context = null): Collection
    {
        return $this->_fields($context)->collect();
    }

    /**
     * Returns all fields that store content in the `elements_sites.content` table.
     *
     * @param  string|string[]|false|null  $context  The field context(s) to fetch fields from. Defaults to [[\craft\services\Fields::$fieldContext]].
     *                                               Set to `false` to get all fields regardless of context.
     * @return Collection<int, FieldInterface> The fields
     */
    public function getFieldsWithContent(mixed $context = null): Collection
    {
        return $this->getAllFields($context)
            ->filter(fn (FieldInterface $field) => $field::dbType() !== null);
    }

    /**
     * Returns all fields that don’t store content in the `elements_sites.content` table.
     *
     * @param  string|string[]|false|null  $context  The field context(s) to fetch fields from. Defaults to [[\craft\services\Fields::$fieldContext]].
     *                                               Set to `false` to get all fields regardless of context.
     * @return Collection<int, FieldInterface> The fields
     */
    public function getFieldsWithoutContent(mixed $context = null): Collection
    {
        return $this->getAllFields($context)
            ->filter(fn (FieldInterface $field) => $field::dbType() === null);
    }

    /**
     * Returns all fields of a certain type.
     *
     * @template T of FieldInterface
     *
     * @param  class-string<T>  $type  The field type
     * @param  string|string[]|false|null  $context  The field context(s) to fetch fields from. Defaults to [[\craft\services\Fields::$fieldContext]].
     *                                               Set to `false` to get all fields regardless of context.
     * @return Collection<int, T> The fields
     */
    public function getFieldsByType(string $type, mixed $context = null): Collection
    {
        return $this->getAllFields($context)
            ->filter(fn (FieldInterface $field) => $field instanceof $type);
    }

    /**
     * Returns a field by its ID.
     *
     * @param  int  $fieldId  The field’s ID
     * @return FieldInterface|null The field, or null if it doesn’t exist
     */
    public function getFieldById(int $fieldId): ?FieldInterface
    {
        return $this->_fields(false)->firstWhere('id', $fieldId);
    }

    /**
     * Returns a field by its UID.
     *
     * @param  string  $fieldUid  The field’s UID
     * @return FieldInterface|null The field, or null if it doesn’t exist
     */
    public function getFieldByUid(string $fieldUid): ?FieldInterface
    {
        return $this->_fields(false)->firstWhere('uid', $fieldUid, true);
    }

    /**
     * Returns a field by its handle and optional context.
     *
     * ---
     *
     * ```php
     * $body = Fields::getFieldByHandle('body');
     * ```
     * ```twig
     * {% set body = craft.fields.getFieldByHandle('body') %}
     * {{ body.instructions }}
     * ```
     *
     * @param  string  $handle  The field’s handle
     * @param  string|string[]|false|null  $context  The field context(s) to fetch fields from. Defaults to [[\craft\services\Fields::$fieldContext]].
     *                                               Set to `false` to get all fields regardless of context.
     * @return FieldInterface|null The field, or null if it doesn’t exist
     */
    public function getFieldByHandle(string $handle, mixed $context = null): ?FieldInterface
    {
        return $this->_fields($context)->firstWhere('handle', $handle, true);
    }

    /**
     * Returns whether a field exists with a given handle and context.
     *
     * @param  string  $handle  The field handle
     * @param  string|null  $context  The field context (defauts to [[\craft\services\Fields::$fieldContext]])
     * @return bool Whether a field with that handle exists
     */
    public function doesFieldWithHandleExist(string $handle, ?string $context = null): bool
    {
        return $this->getAllFields($context)->contains('handle', '===', $handle);
    }

    /**
     * Returns the config for the given field.
     *
     * @return FieldConfig
     */
    public function createFieldConfig(FieldInterface $field): array
    {
        /**
         * Normalize the settings as some fields still define their
         * attributes in a simple array instead of key => value.
         */
        $settings = collect($field->getSettings())->mapWithKeys(function ($value, $key) use ($field) {
            if (! is_int($key)) {
                return [$key => $value];
            }

            if (property_exists($field, $value)) {
                return [$value => $field->$value];
            }

            if (method_exists($field, 'get'.ucfirst($value))) {
                return [$value => $field->{'get'.ucfirst($value)}()];
            }

            return [$key => $value];
        })->all();

        return [
            'name' => $field->name,
            'handle' => $field->handle,
            'instructions' => $field->instructions,
            'searchable' => $field->searchable,
            'translationMethod' => $field->translationMethod,
            'translationKeyFormat' => $field->translationKeyFormat,
            'type' => $field instanceof MissingField ? $field->expectedType : $field::class,
            'settings' => ProjectConfigHelper::packAssociativeArrays(
                $field instanceof MissingField ? ($field->settings ?? []) : $settings,
            ),
        ];
    }

    /**
     * Saves a field.
     *
     * @param  FieldInterface  $field  The Field to be saved
     * @param  bool  $runValidation  Whether the field should be validated
     * @return bool Whether the field was saved successfully
     *
     * @throws Throwable if reasons
     */
    public function saveField(FieldInterface $field, bool $runValidation = true): bool
    {
        if ($field instanceof MissingField) {
            $error = $field->errorMessage ?? "Unable to find component class '$field->expectedType'.";

            throw ValidationException::withMessages([
                'type' => $error,
            ]);
        }

        $isNewField = $field->getIsNew();

        event(new FieldSaving($field, $isNewField));

        if (! $field->beforeSave($isNewField)) {
            return false;
        }

        if ($runValidation && ! $field->validate()) {
            Log::info('Field not saved due to validation error.', [__METHOD__]);

            return false;
        }

        $this->prepFieldForSave($field);
        $configData = $this->createFieldConfig($field);
        $appliedConfig = false;

        // Only store field data in the project config for global context
        if ($field->context === 'global') {
            $configPath = ProjectConfig::PATH_FIELDS.'.'.$field->uid;
            $appliedConfig = ProjectConfigFacade::set(
                path: $configPath,
                value: $configData,
                message: "Save field “{$field->handle}”",
            );
        }

        if (! $appliedConfig) {
            // If it’s not a global field, or there weren't any changes in the main field settings, apply the save to the DB + call afterSave()
            $this->applyFieldSave($field->uid, $configData, $field->context);
        }

        if ($isNewField) {
            $field->id = DB::table(Table::FIELDS)->idByUid($field->uid);
        }

        return true;
    }

    /**
     * Preps a field to be saved.
     */
    public function prepFieldForSave(FieldInterface $field): void
    {
        // Clear the translation key format if not using a custom translation method
        if ($field->translationMethod !== TranslationMethod::Custom->value) {
            $field->translationKeyFormat = null;
        }

        $isNew = $field->getIsNew();

        // Make sure it's got a UUID
        if ($isNew) {
            if (empty($field->uid)) {
                $field->uid = Str::uuid7()->toString();
            }
        } elseif (! $field->uid) {
            $field->uid = DB::table(Table::FIELDS)->uidById($field->id);
        }

        // Store with all the populated data for future reference.
        $this->_savingFields[$field->uid] = $field;
    }

    /**
     * Handle field changes.
     *
     *
     * @throws Throwable
     */
    public function handleChangedField(ConfigEvent $event): void
    {
        DB::connection()->getPdo()->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        $data = $event->newValue;
        $fieldUid = $event->tokenMatches[0];

        if (! is_array($data)) {
            return;
        }

        $this->applyFieldSave($fieldUid, $data, 'global');
    }

    /**
     * Deletes a field by its ID.
     *
     * @param  int  $fieldId  The field’s ID
     * @return bool Whether the field was deleted successfully
     */
    public function deleteFieldById(int $fieldId): bool
    {
        $field = $this->getFieldById($fieldId);

        if (! $field) {
            return false;
        }

        return $this->deleteField($field);
    }

    /**
     * Deletes a field.
     *
     * @param  FieldInterface  $field  The field
     * @return bool Whether the field was deleted successfully
     *
     * @throws Throwable if reasons
     */
    public function deleteField(FieldInterface $field): bool
    {
        event(new FieldDeleting($field));

        if (! $field->beforeDelete()) {
            return false;
        }

        if ($field->context === 'global') {
            ProjectConfigFacade::remove(
                path: ProjectConfig::PATH_FIELDS.'.'.$field->uid,
                message: "Delete the “{$field->handle}” field",
            );
        } else {
            $this->applyFieldDelete($field->uid);
        }

        return true;
    }

    /**
     * Handle a field getting deleted.
     */
    public function handleDeletedField(ConfigEvent $event): void
    {
        $fieldUid = $event->tokenMatches[0];

        $this->applyFieldDelete($fieldUid);
    }

    /**
     * Applies a field delete to the database.
     *
     *
     * @throws Throwable if database error
     */
    public function applyFieldDelete(string $fieldUid): void
    {
        $fieldRecord = $this->_getFieldModel($fieldUid);

        if (! $fieldRecord->id) {
            return;
        }

        $field = $this->getFieldById($fieldRecord->id);

        DB::beginTransaction();

        try {
            $field->beforeApplyDelete();

            // Soft-delete the row in `fields`
            DB::table(Table::FIELDS)->softDelete($fieldRecord->id);

            $field->afterDelete();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        // Clear caches
        $this->invalidateCaches();

        event(new FieldDeleted($field));

        // Invalidate all element caches
        $this->elementCaches->invalidateAll();
    }

    /**
     * Refreshes the internal field cache.
     *
     * This should be called whenever a field is updated or deleted directly in
     * the database, rather than going through this service.
     */
    public function refreshFields(): void
    {
        $this->invalidateCaches();
    }

    /**
     * Returns all the field layouts that contain the given field.
     *
     *
     * @return Collection<int, FieldLayout>
     */
    public function findFieldUsages(FieldInterface $field): Collection
    {
        if (! isset($field->id)) {
            return new Collection;
        }

        return $this->getAllLayouts()->filter(fn (FieldLayout $layout) => ComponentHelper::validateComponentClass($layout->type, ElementInterface::class) &&
            $layout->isFieldIncluded(fn (BaseField $layoutField) => (
                $layoutField instanceof CustomField &&
                $layoutField->getFieldUid() === $field->uid
            )));
    }

    public function merge(
        FieldInterface&MergeableFieldInterface $persistingField,
        FieldInterface&MergeableFieldInterface $outgoingField,
    ): FieldMergeResult {
        $projectConfigService = app(ProjectConfig::class);
        $muteEvents = $projectConfigService->muteEvents;
        $updatedLayouts = 0;

        $projectConfigService->muteEvents = true;

        try {
            foreach ($this->findFieldUsages($outgoingField) as $layout) {
                $changed = false;

                foreach ($layout->getCustomFieldElements() as $layoutElement) {
                    if ($layoutElement->getFieldUid() !== $outgoingField->uid) {
                        continue;
                    }

                    $layoutElement->label = $this->layoutElementOverride($persistingField->name, $outgoingField->name, $layoutElement->label);
                    $layoutElement->handle = $this->layoutElementOverride($persistingField->handle, $outgoingField->handle, $layoutElement->handle);
                    $layoutElement->instructions = $this->layoutElementOverride($persistingField->instructions, $outgoingField->instructions, $layoutElement->instructions);

                    $layoutElement->setField($persistingField);
                    $changed = true;
                }

                if (! $changed) {
                    continue;
                }

                $layout->id ??= DB::table(Table::FIELDLAYOUTS)->idByUid($layout->uid);

                if ($layout->id) {
                    $this->saveLayout($layout);
                }

                if ($layout->uid) {
                    $projectConfigOccurrences = $projectConfigService->find(fn (array $item) => isset($item[$layout->uid]));

                    foreach ($projectConfigOccurrences as $path => $item) {
                        $projectConfigService->set("$path.$layout->uid", $layout->getConfig());
                    }
                }

                $updatedLayouts++;
            }
        } finally {
            $projectConfigService->muteEvents = $muteEvents;
        }

        $this->deleteField($outgoingField);

        $migrationName = sprintf('%s_merge_%s_into_%s', now('UTC')->format('Y_m_d_His'), $outgoingField->handle, $persistingField->handle);
        $migrationPath = database_path("migrations/{$migrationName}.php");

        ob_start();
        File::getRequire(Path::package('stubs/field-merge.php.stub'), [
            'persistingFieldUid' => $persistingField->uid,
            'outgoingFieldUid' => $outgoingField->uid,
        ]);
        $content = ob_get_clean();

        File::put($migrationPath, $content);

        app(Migrator::class)->track('content')->run();

        return new FieldMergeResult(
            updatedLayouts: $updatedLayouts,
            migrationPath: $migrationPath,
        );
    }

    private function layoutElementOverride(?string $persistingFieldValue, ?string $outgoingFieldValue, ?string $override): ?string
    {
        $persistingFieldValue = ($persistingFieldValue === '' ? null : $persistingFieldValue);
        $outgoingFieldValue = ($outgoingFieldValue === '' ? null : $outgoingFieldValue);
        $override = ($override === '' ? null : $override);
        $expected = $override ?? $outgoingFieldValue;

        return $persistingFieldValue !== $expected ? $expected : null;
    }

    /**
     * @return array<int,FieldLayout[]>
     */
    private function allFieldUsages(): array
    {
        $usages = [];

        foreach ($this->getAllLayouts() as $layout) {
            $uniqueFieldIds = [];
            foreach ($layout->getCustomFields() as $field) {
                $uniqueFieldIds[$field->id] = true;
            }
            foreach (array_keys($uniqueFieldIds) as $fieldId) {
                $usages[$fieldId][] = $layout;
            }
        }

        return $usages;
    }

    // Layouts
    // -------------------------------------------------------------------------

    /**
     * Returns a memoizable array of all field layouts.
     *
     * @return MemoizableArray<FieldLayout>
     */
    private function _layouts(): MemoizableArray
    {
        if (isset($this->_layouts)) {
            return $this->_layouts;
        }

        if (Cms::isInstalled()) {
            $layoutConfigs = $this->_createLayoutQuery()->get()->all();
        } else {
            $layoutConfigs = [];
        }

        /**
         * @var MemoizableArray<FieldLayout> $layouts
         * @var FieldLayout[] $layoutConfigs
         */
        $layouts = new MemoizableArray(
            elements: $layoutConfigs,
            normalizer: fn (object $config) => $this->_layoutFromConfig((array) $config),
        );

        return $this->_layouts = $layouts;
    }

    /** @param array<string, mixed> $config */
    private function _layoutFromConfig(array $config): FieldLayout
    {
        $nestedConfig = Arr::pull($config, 'config');

        if ($nestedConfig) {
            $config += is_string($nestedConfig) ? JsonHelper::decode($nestedConfig) : $nestedConfig;
        }

        return $this->createLayout($config);
    }

    /**
     * Returns all saved field layouts.
     *
     * @return Collection<int, FieldLayout>
     */
    public function getAllLayouts(): Collection
    {
        return $this->_layouts()->collect();
    }

    /**
     * Returns a field layout by its ID.
     *
     * @param  int  $layoutId  The field layout’s ID
     * @param  bool  $withTrashed  Whether to return the field layout even if it’s soft-deleted
     * @return FieldLayout|null The field layout, or null if it doesn’t exist
     */
    public function getLayoutById(int $layoutId, bool $withTrashed = false): ?FieldLayout
    {
        $layout = $this->_layouts()->firstWhere('id', $layoutId);

        if ($layout === null && $withTrashed) {
            $config = $this->_createLayoutQuery(true)->where('id', $layoutId)->first();
            if ($config) {
                return $this->_layoutFromConfig((array) $config);
            }
        }

        return $layout;
    }

    /**
     * Returns a field layout by its UUID.
     *
     * @param  string  $uid  The field layout’s UUID
     * @return FieldLayout|null The field layout, or null if it doesn’t exist
     */
    public function getLayoutByUid(string $uid): ?FieldLayout
    {
        return $this->_layouts()->firstWhere('uid', $uid);
    }

    /**
     * Returns field layouts by their IDs.
     *
     * @param  int[]  $layoutIds  The field layouts’ IDs
     * @return Collection<int, FieldLayout> The field layouts
     */
    public function getLayoutsByIds(array $layoutIds): Collection
    {
        return $this->_layouts()->whereIn('id', $layoutIds)->collect();
    }

    /**
     * Returns a field layout by its associated element type.
     *
     * @param  class-string<ElementInterface>  $type  The associated element type
     * @param  bool  $create  Whether to create a field layout if one doesn’t exist
     * @return FieldLayout|null The field layout
     */
    public function getLayoutByType(string $type, bool $create = true): ?FieldLayout
    {
        $layout = $this->_layouts()->firstWhere('type', $type);

        if (! $layout && $create) {
            return new FieldLayout(['type' => $type]);
        }

        return $layout;
    }

    /**
     * Returns all of the field layouts associated with a given element type.
     *
     * @param  class-string<ElementInterface>  $type
     * @return Collection<int, FieldLayout> The field layouts
     */
    public function getLayoutsByType(string $type): Collection
    {
        return $this->_layouts()->where('type', $type)->collect();
    }

    /**
     * Creates a field layout from the given config.
     *
     * @param  array<string, mixed>|string  $config
     */
    public function createLayout(array|string $config): FieldLayout
    {
        if (is_string($config)) {
            $config = JsonHelper::decode($config);
        }

        unset($config['class']);

        return new FieldLayout($config);
    }

    /**
     * Creates a field layout element instance from its config.
     *
     * @template T of \CraftCms\Cms\FieldLayout\FieldLayoutElement
     *
     * @phpstan-param array{type:class-string<T>} $config
     *
     * @return T
     *
     * @throws InvalidArgumentException if `$config['type']` does not implement [[FieldLayoutElement]]
     */
    public function createLayoutElement(array $config): FieldLayoutElement
    {
        $type = Arr::pull($config, 'type');

        if (! $type) {
            throw new InvalidArgumentException("Invalid field layout element class: $type");
        }

        if (class_exists($type) && ! is_subclass_of($type, FieldLayoutElement::class)) {
            throw new InvalidArgumentException("Invalid field layout element class: $type");
        }

        try {
            ComponentHelper::validateComponentClass($type, FieldLayoutElement::class, true);
        } catch (MissingComponentException $exception) {
            return new MissingLayoutElement([
                'expectedType' => $type,
                'errorMessage' => $exception->getMessage(),
                'settings' => $config,
                'uid' => $config['uid'] ?? null,
                'width' => $config['width'] ?? 100,
            ]);
        }

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return new $type(config: $config);
    }

    /**
     * Assembles a field layout from post data.
     *
     * @param  string|null  $namespace  The namespace that the form data was posted in, if any
     * @return FieldLayout The field layout
     */
    public function assembleLayoutFromPost(?string $namespace = null): FieldLayout
    {
        $paramPrefix = $namespace ? rtrim($namespace, '.').'.' : '';

        $config = Request::input("{$paramPrefix}fieldLayout");
        $config = is_array($config) ? $config : JsonHelper::decode($config);
        $config ??= [];

        if (Request::has("{$paramPrefix}generatedFields")) {
            $config['generatedFields'] = Request::input("{$paramPrefix}generatedFields") ?: null;
        }

        $layout = $this->createLayout($config);

        // Make sure all the elements have a dateAdded value set
        foreach ($layout->getTabs() as $tab) {
            foreach ($tab->getElements() as $layoutElement) {
                $layoutElement->dateAdded ??= now();
            }
        }

        return $layout;
    }

    /**
     * Saves a field layout.
     *
     * @param  FieldLayout  $layout  The field layout
     * @param  bool  $runValidation  Whether the layout should be validated
     * @return bool Whether the field layout was saved successfully
     *
     * @throws Exception if $layout->id is set to an invalid layout ID
     */
    public function saveLayout(FieldLayout $layout, bool $runValidation = true): bool
    {
        if (! $layout->id) {
            // Maybe the ID just wasn't known
            $layout->id = DB::table(Table::FIELDLAYOUTS)->idByUid($layout->uid);
        }

        $isNewLayout = ! $layout->id;

        event(new FieldLayoutSaving($layout, $isNewLayout));

        if ($runValidation && ! $layout->validate()) {
            Log::info('Field layout not saved due to validation error.', [__METHOD__]);

            return false;
        }

        if (! $isNewLayout) {
            // Get the current layout
            $layoutModel = FieldLayoutModel::withTrashed()->findOrFail($layout->id);
            $previousConfig = $layoutModel->config;
        } else {
            $layoutModel = new FieldLayoutModel;
            $previousConfig = null;
        }

        // Save the layout
        $layoutModel->type = $layout->type;
        $layoutModel->config = $layout->getConfig();
        $layoutModel->uid = $layout->uid;

        if (! $isNewLayout) {
            $layoutModel->id = $layout->id;
        }

        if ($layoutModel->dateDeleted) {
            $layoutModel->dateDeleted = null;
        }

        $layoutModel->save();

        if ($isNewLayout) {
            $layout->id = $layoutModel->id;
        }

        $layout->uid = $layoutModel->uid;

        event(new FieldLayoutSaved($layout, $isNewLayout, $previousConfig));

        // Clear caches
        $this->invalidateCaches();

        return true;
    }

    /**
     * Deletes a field layout(s) by its ID.
     *
     * @param  int|int[]  $layoutId  The field layout’s ID
     * @param  bool  $hardDelete  Whether the field layout should be hard-deleted immediately, instead of soft-deleted
     * @return bool Whether the field layout was deleted successfully
     */
    public function deleteLayoutById(array|int $layoutId, bool $hardDelete = false): bool
    {
        if (! $layoutId) {
            return false;
        }

        foreach (Arr::wrap($layoutId) as $thisLayoutId) {
            if ($layout = $this->getLayoutById($thisLayoutId)) {
                $this->deleteLayout($layout, $hardDelete);
            }
        }

        return true;
    }

    /**
     * Deletes a field layout.
     *
     * @param  FieldLayout  $layout  The field layout
     * @param  bool  $hardDelete  Whether the field layout should be hard-deleted immediately, instead of soft-deleted
     * @return bool Whether the field layout was deleted successfully
     */
    public function deleteLayout(FieldLayout $layout, bool $hardDelete = false): bool
    {
        event(new FieldLayoutDeleting($layout));

        if ($hardDelete) {
            DB::table(Table::FIELDLAYOUTS)->delete($layout->id);
        } else {
            DB::table(Table::FIELDLAYOUTS)->softDelete($layout->id);
        }

        event(new FieldLayoutDeleted($layout));

        // Clear caches
        $this->_layouts = null;
        $this->_allFieldHandles = null;
        $this->_allGeneratedFieldHandles = null;

        return true;
    }

    /**
     * Deletes field layouts associated with a given element type.
     *
     * @param  class-string<ElementInterface>  $type  The element type
     * @return bool Whether the field layouts were deleted successfully
     */
    public function deleteLayoutsByType(string $type): bool
    {
        $affectedRows = DB::table(Table::FIELDLAYOUTS)
            ->where('type', $type)
            ->softDelete();

        // Clear caches
        $this->_layouts = null;
        $this->_allFieldHandles = null;
        $this->_allGeneratedFieldHandles = null;

        return (bool) $affectedRows;
    }

    /**
     * Restores a field layout by its ID.
     *
     * @param  int  $id  The field layout’s ID
     * @return bool Whether the layout was restored successfully
     */
    public function restoreLayoutById(int $id): bool
    {
        $affectedRows = DB::table(Table::FIELDLAYOUTS)->restore($id);

        // Clear caches
        $this->_layouts = null;
        $this->_allFieldHandles = null;
        $this->_allGeneratedFieldHandles = null;

        return (bool) $affectedRows;
    }

    /**
     * Applies a field save to the database.
     */
    /** @param FieldConfig $data */
    public function applyFieldSave(string $fieldUid, array $data, string $context): void
    {
        $fieldRecord = $this->_getFieldModel($fieldUid, true);
        $isNewField = ! $fieldRecord->exists;
        $oldSettings = $fieldRecord->getOriginal('settings');
        $oldField = ! $isNewField ? $this->getFieldById($fieldRecord->id) : null;

        // For control panel save requests, make sure we have all the custom data already saved on the object.
        $field = $this->_savingFields[$fieldUid] ?? null;

        event(new FieldSaveApplying($oldField, $data));

        DB::beginTransaction();

        try {
            // Track whether we should remove the field’s search indexes after save
            $searchable = $data['searchable'] ?? false;
            $deleteSearchIndexes = ! $isNewField && ! $searchable && $fieldRecord->searchable;

            // Clear the translation key format if not using a custom translation method
            if ($data['translationMethod'] !== TranslationMethod::Custom->value) {
                $data['translationKeyFormat'] = null;
            }

            if (! empty($data['settings']) && is_array($data['settings'])) {
                $data['settings'] = ProjectConfigHelper::unpackAssociativeArrays($data['settings']);
            }

            $fieldRecord->uid = $fieldUid;
            $fieldRecord->name = $data['name'];
            $fieldRecord->handle = $data['handle'];
            $fieldRecord->context = $context;
            $fieldRecord->instructions = $data['instructions'];
            $fieldRecord->searchable = (bool) $searchable;
            $fieldRecord->translationMethod = $data['translationMethod'];
            $fieldRecord->translationKeyFormat = $data['translationKeyFormat'];
            $fieldRecord->type = $data['type'];
            $fieldRecord->setAttribute('settings', $data['settings'] ?? null);

            if ($fieldRecord->dateDeleted) {
                $fieldRecord->dateDeleted = null;
            }

            $fieldRecord->save();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        // Clear caches
        $this->refreshFields();

        // Now get the field, if it's not a field save request
        $field ??= $this->getFieldById($fieldRecord->id);
        if ($isNewField) {
            $field->id = $fieldRecord->id;
        }

        if (! $isNewField) {
            // Set the old field handle and settings on the model in case the field type needs to do something with it
            $field->oldHandle = $fieldRecord->getOldHandle();
            $field->oldSettings = is_string($oldSettings) ? JsonHelper::decode($oldSettings) : null;
        }

        $field->afterSave($isNewField);

        event(new FieldSaved($field, $isNewField));

        // If we just dropped `searchable`, delete the field’s search indexes immediately.
        if ($deleteSearchIndexes) {
            DB::table(Table::SEARCHINDEX)
                ->where('attribute', 'field')
                ->where('fieldId', $field->id)
                ->delete();
        }

        // Invalidate all element caches
        $this->elementCaches->invalidateAll();
    }

    /**
     * Returns data for the Fields index page in the control panel.
     *
     *
     * @internal
     *
     * @return array{0:PaginationData, 1:list<FieldTableRow>}
     */
    public function getTableData(
        int $page,
        int $limit,
        ?string $searchTerm,
        string $orderBy = 'name',
        int $sortDir = SORT_ASC,
    ): array {
        $searchTerm = $searchTerm ? trim($searchTerm) : $searchTerm;
        $pageParam = Cms::config()->getPageTriggerParam();
        $query = $this->_createFieldQuery()
            ->where('context', 'global')
            ->reorder();

        $sortDir = $sortDir === SORT_ASC ? 'asc' : 'desc';

        if ($orderBy === 'type') {
            $types = $this->getAllFieldTypes()->sortBy(
                fn (string $class) => $class::displayName(),
                descending: $sortDir === 'desc',
            );

            $query->orderBy(new FixedOrderExpression('type', $types->all()))
                ->orderBy('name', $sortDir)
                ->orderBy('handle', $sortDir);
        } else {
            $query->orderBy($orderBy, $sortDir);
            if ($orderBy === 'name') {
                $query->orderBy('handle', $sortDir);
            }
        }

        if ($searchTerm !== null && $searchTerm !== '') {
            $searchParams = $this->_getSearchParams($searchTerm);
            if (! empty($searchParams)) {
                $query->where(function (Builder $query) use ($searchParams) {
                    foreach ($searchParams as $param) {
                        $query->orWhere($param[0], $param[1], $param[2]);
                    }
                });
            }
        }

        $paginator = $query->paginate(
            perPage: $limit,
            pageName: $pageParam,
            page: $page,
        );
        $result = $paginator->getCollection();

        $tableData = [];
        $usages = $this->allFieldUsages();

        foreach ($result as $item) {
            $field = $this->createField((array) $item);

            $tableData[] = [
                'id' => $field->id,
                'title' => t($field->name, category: 'site'),
                'translatable' => $field->getIsTranslatable(null)
                    ? ($field->getTranslationDescription(null) ?? t('This field is translatable.'))
                    : false,
                'searchable' => $field->searchable,
                'url' => $field->getCpEditUrl(),
                'handle' => $field->handle,
                'type' => [
                    'isMissing' => $field instanceof MissingField,
                    'label' => $field instanceof MissingField ? $field->expectedType : $field::displayName(),
                    'icon' => $field instanceof Iconic ? Icons::resolveIconData($field->getIcon()) : null,
                ],
                'usages' => isset($usages[$field->id])
                    ? t('{count, number} {count, plural, =1{layout} other{layouts}}', [
                        'count' => count($usages[$field->id]),
                    ])
                    : null,
            ];
        }

        $paginator->appends(request()->except($pageParam));

        $pagination = Arr::only($paginator->toArray(), [
            'total',
            'per_page',
            'current_page',
            'last_page',
            'next_page_url',
            'prev_page_url',
            'from',
            'to',
        ]);

        return [$pagination, $tableData];
    }

    /**
     * Returns the array of sql "like" params to be used in the 'where' param for the query.
     */
    /** @return list<array{string, 'ilike'|'like', string}> */
    private function _getSearchParams(string $term): array
    {
        $searchParams = ['name', 'handle', 'instructions', 'type'];
        $searchQueries = [];

        if ($term === '') {
            return $searchQueries;
        }

        $isPgqsl = DB::isPgsql();

        foreach ($searchParams as $param) {
            $searchQueries[] = [$param, $isPgqsl ? 'ilike' : 'like', '%'.$term.'%'];
        }

        return $searchQueries;
    }

    private function _createFieldQuery(): Builder
    {
        return DB::table(Table::FIELDS)
            ->select([
                'fields.id',
                'fields.dateCreated',
                'fields.dateUpdated',
                'fields.name',
                'fields.handle',
                'fields.context',
                'fields.instructions',
                'fields.searchable',
                'fields.translationMethod',
                'fields.translationKeyFormat',
                'fields.type',
                'fields.settings',
                'fields.uid',
            ])
            ->orderBy('fields.name')
            ->orderBy('fields.handle')
            ->whereNull('fields.dateDeleted');
    }

    private function _createLayoutQuery(bool $withTrashed = false): Builder
    {
        return DB::table(Table::FIELDLAYOUTS)
            ->select([
                'id',
                'type',
                'config',
                'uid',
            ])
            ->unless(
                $withTrashed,
                fn (Builder $query) => $query->whereNull('dateDeleted'),
            );
    }

    /**
     * Returns a field model for a given UID
     */
    private function _getFieldModel(string $uid, bool $withTrashed = false): Models\Field
    {
        return Models\Field::withTrashed($withTrashed)
            ->where('uid', $uid)
            ->first() ?? new Models\Field;
    }
}
