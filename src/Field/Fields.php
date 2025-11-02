<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldLayoutElement;
use craft\base\MemoizableArray;
use craft\behaviors\CustomFieldBehavior;
use craft\errors\MissingComponentException;
use craft\fieldlayoutelements\BaseField;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\AdminTable;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\Cp;
use craft\helpers\Db as DbHelper;
use craft\models\FieldLayout;
use craft\records\FieldLayout as FieldLayoutRecord;
use CraftCms\Cms\Database\Expressions\FixedOrderExpression;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Field\Addresses as AddressesField;
use CraftCms\Cms\Field\Assets as AssetsField;
use CraftCms\Cms\Field\Categories as CategoriesField;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Entries as EntriesField;
use CraftCms\Cms\Field\Events\ApplyingFieldDelete;
use CraftCms\Cms\Field\Events\ApplyingFieldSave;
use CraftCms\Cms\Field\Events\DefineCompatibleFieldTypes;
use CraftCms\Cms\Field\Events\FieldDeleted;
use CraftCms\Cms\Field\Events\FieldDeleting;
use CraftCms\Cms\Field\Events\FieldLayoutDeleted;
use CraftCms\Cms\Field\Events\FieldLayoutDeleting;
use CraftCms\Cms\Field\Events\FieldLayoutSaved;
use CraftCms\Cms\Field\Events\FieldLayoutSaving;
use CraftCms\Cms\Field\Events\FieldSaved;
use CraftCms\Cms\Field\Events\FieldSaving;
use CraftCms\Cms\Field\Events\RegisterFieldTypes;
use CraftCms\Cms\Field\Events\RegisterNestedEntryFieldTypes;
use CraftCms\Cms\Field\Matrix as MatrixField;
use CraftCms\Cms\Field\Table as TableField;
use CraftCms\Cms\Field\Tags as TagsField;
use CraftCms\Cms\Field\Users as UsersField;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use CraftCms\Cms\Shared\Models\Info;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\ProjectConfig as ProjectConfigFacade;
use CraftCms\Cms\Support\Json as JsonHelper;
use CraftCms\Cms\Support\Str;
use Exception;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use PDO;
use Throwable;

use function CraftCms\Cms\t;

#[Singleton]
final class Fields
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

    private array $_savingFields = [];

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
     * @return Collection<class-string<FieldInterface>>
     */
    public function getAllFieldTypes(): Collection
    {
        $fieldTypes = collect([
            AddressesField::class,
            AssetsField::class,
            ButtonGroup::class,
            CategoriesField::class,
            Checkboxes::class,
            Color::class,
            ContentBlock::class,
            Country::class,
            Date::class,
            Dropdown::class,
            Email::class,
            EntriesField::class,
            Icon::class,
            Json::class,
            Lightswitch::class,
            Link::class,
            MatrixField::class,
            Money::class,
            MultiSelect::class,
            Number::class,
            PlainText::class,
            RadioButtons::class,
            Range::class,
            TableField::class,
            TagsField::class,
            Time::class,
            UsersField::class,
        ]);

        if (Event::hasListeners(RegisterFieldTypes::class)) {
            Event::dispatch($event = new RegisterFieldTypes($fieldTypes));

            return $event->types;
        }

        return $fieldTypes;
    }

    /**
     * Returns all field types that have a column in the content table.
     *
     * @phpstan-return Collection<class-string<FieldInterface>>
     */
    public function getFieldTypesWithContent(): Collection
    {
        return $this->getAllFieldTypes()
            /** @var class-string<FieldInterface> $class */
            ->filter(fn (string $class) => $class::dbType() !== null)
            ->values();
    }

    /**
     * Returns all field types whose column types are considered compatible with a given field.
     *
     * @param  FieldInterface  $field  The current field to base compatible fields on
     * @param  bool  $includeCurrent  Whether $field's class should be included
     * @return Collection<class-string<FieldInterface>>
     */
    public function getCompatibleFieldTypes(FieldInterface $field, bool $includeCurrent = true): Collection
    {
        // If the field has any validation errors and has an ID, swap it with the saved field
        if (! $field->getIsNew() && $field->hasErrors()) {
            $field = $this->getFieldById($field->id);
        }

        $types = new Collection;
        $dbType = $field::dbType();

        if (is_string($dbType)) {
            foreach ($this->getAllFieldTypes() as $class) {
                /** @var class-string<FieldInterface> $class */
                if (
                    ($includeCurrent || $class !== $field::class) &&
                    $this->areFieldTypesCompatible($field::class, $class)
                ) {
                    $types->add($class);
                }
            }
        }

        // Make sure the current field class is in there if it's supposed to be
        /** @var FieldInterface $field */
        if ($includeCurrent && $types->doesntContain($field::class)) {
            $types->add($field::class);
        }

        if (Event::hasListeners(DefineCompatibleFieldTypes::class)) {
            Event::dispatch($event = new DefineCompatibleFieldTypes($field, $types));

            return $event->compatibleTypes;
        }

        return $types;
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

        return DbHelper::areColumnTypesCompatible($dbTypeA, $dbTypeB);
    }

    /**
     * Returns all field types which manage nested entries.
     *
     * @return Collection<class-string<ElementContainerFieldInterface>> The field type classes which manage nested entries
     */
    public function getNestedEntryFieldTypes(): Collection
    {
        $fieldTypes = collect([
            MatrixField::class,
        ]);

        if (Event::hasListeners(RegisterNestedEntryFieldTypes::class)) {
            Event::dispatch($event = new RegisterNestedEntryFieldTypes($fieldTypes));

            return $event->types;
        }

        return $fieldTypes;
    }

    /**
     * Returns all available relational field type classes.
     *
     * @return Collection<class-string<BaseRelationField>> The available relational field type classes
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
     * @param  class-string<T>|array  $config  The field’s class name, or its config, with a `type` value and optionally a `settings` value
     *
     * @phpstan-param class-string<T>|array{type:class-string<T>,id?:int|string,uid?:string} $config
     *
     * @return T The field
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
     * @return Collection<FieldInterface> The fields
     */
    public function getAllFields(mixed $context = null): Collection
    {
        return collect($this->_fields($context)->all());
    }

    /**
     * Returns all fields that store content in the `elements_sites.content` table.
     *
     * @param  string|string[]|false|null  $context  The field context(s) to fetch fields from. Defaults to [[\craft\services\Fields::$fieldContext]].
     *                                               Set to `false` to get all fields regardless of context.
     * @return Collection<FieldInterface> The fields
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
     * @return Collection<FieldInterface> The fields
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
     * @return Collection<T> The fields
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
            'columnSuffix' => property_exists($field, 'columnSuffix') ? $field->columnSuffix : null,
            'instructions' => $field->instructions,
            'searchable' => $field->searchable,
            'translationMethod' => $field->translationMethod,
            'translationKeyFormat' => $field->translationKeyFormat,
            'type' => $field::class,
            'settings' => ProjectConfigHelper::packAssociativeArrays($settings),
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

        if (Event::hasListeners(FieldSaving::class)) {
            Event::dispatch(new FieldSaving($field, $isNewField));
        }

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
        if ($field->translationMethod !== Field::TRANSLATION_METHOD_CUSTOM) {
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
        if (Event::hasListeners(FieldDeleting::class)) {
            Event::dispatch(new FieldDeleting($field));
        }

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

        if (Event::hasListeners(ApplyingFieldDelete::class)) {
            Event::dispatch(new ApplyingFieldDelete($field));
        }

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
        $this->_fields = null;

        // Update the field version
        $this->updateFieldVersion();

        if (Event::hasListeners(FieldDeleted::class)) {
            Event::dispatch(new FieldDeleted($field));
        }

        // Invalidate all element caches
        Craft::$app->getElements()->invalidateAllCaches();
    }

    /**
     * Refreshes the internal field cache.
     *
     * This should be called whenever a field is updated or deleted directly in
     * the database, rather than going through this service.
     */
    public function refreshFields(): void
    {
        $this->_fields = null;

        $this->updateFieldVersion();
    }

    /**
     * Returns all the field layouts that contain the given field.
     *
     *
     * @return Collection<FieldLayout>
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

        if (Craft::$app->getIsInstalled()) {
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
     * @return Collection<FieldLayout>
     */
    public function getAllLayouts(): Collection
    {
        return collect($this->_layouts()->all());
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
     * @return Collection<FieldLayout> The field layouts
     */
    public function getLayoutsByIds(array $layoutIds): Collection
    {
        return collect($this->_layouts()->whereIn('id', $layoutIds)->all());
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
     * @return Collection<FieldLayout> The field layouts
     */
    public function getLayoutsByType(string $type): Collection
    {
        return collect($this->_layouts()->where('type', $type)->all());
    }

    /**
     * Creates a field layout from the given config.
     */
    public function createLayout(array $config): FieldLayout
    {
        $config['class'] = FieldLayout::class;

        return Craft::createObject($config);
    }

    /**
     * Creates a field layout element instance from its config.
     *
     * @template T of FieldLayoutElement
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

        throw_if(! $type || ! is_subclass_of($type, FieldLayoutElement::class), InvalidArgumentException::class, "Invalid field layout element class: $type");

        $config['class'] = $type;

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Craft::createObject($config);
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

        $config = JsonHelper::decode(Request::get("{$paramPrefix}fieldLayout"));
        $config['generatedFields'] = Request::get("{$paramPrefix}generatedFields") ?: null;
        $config['cardView'] = Request::get("{$paramPrefix}cardView") ?: null;
        $config['cardThumbAlignment'] = Request::get($paramPrefix.'thumbAlignment');

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

        if (Event::hasListeners(FieldLayoutSaving::class)) {
            Event::dispatch(new FieldLayoutSaving($layout, $isNewLayout));
        }

        if ($runValidation && ! $layout->validate()) {
            Log::info('Field layout not saved due to validation error.', [__METHOD__]);

            return false;
        }

        if (! $isNewLayout) {
            // Get the current layout
            /** @var FieldLayoutRecord|null $layoutRecord */
            $layoutRecord = FieldLayoutRecord::findWithTrashed()
                ->andWhere(['id' => $layout->id])
                ->one();

            throw_unless($layoutRecord, Exception::class, 'Invalid field layout ID: '.$layout->id);
        } else {
            $layoutRecord = new FieldLayoutRecord;
        }

        // Save the layout
        $layoutRecord->type = $layout->type;
        $layoutRecord->config = $layout->getConfig();
        $layoutRecord->uid = $layout->uid;

        if (! $isNewLayout) {
            $layoutRecord->id = $layout->id;
        }

        if ($layoutRecord->dateDeleted) {
            $layoutRecord->restore();
        } else {
            $layoutRecord->save(false);
        }

        if ($isNewLayout) {
            $layout->id = $layoutRecord->id;
        }

        $layout->uid = $layoutRecord->uid;

        if (Event::hasListeners(FieldLayoutSaved::class)) {
            Event::dispatch(new FieldLayoutSaved($layout, $isNewLayout));
        }

        // Clear caches
        $this->_layouts = null;

        // Refresh CustomFieldBehavior in case any custom field handles were just added/removed
        $this->updateFieldVersion();

        // Tell the current CustomFieldBehavior class about the fields, since they might have custom handles
        foreach ($layout->getCustomFieldElements() as $layoutElement) {
            if (isset($layoutElement->handle)) {
                CustomFieldBehavior::$fieldHandles[$layoutElement->handle] = true;
            }
        }

        return true;
    }

    /**
     * Deletes a field layout(s) by its ID.
     *
     * @param  int|int[]  $layoutId  The field layout’s ID
     * @return bool Whether the field layout was deleted successfully
     */
    public function deleteLayoutById(array|int $layoutId): bool
    {
        if (! $layoutId) {
            return false;
        }

        foreach (Arr::wrap($layoutId) as $thisLayoutId) {
            if ($layout = $this->getLayoutById($thisLayoutId)) {
                $this->deleteLayout($layout);
            }
        }

        return true;
    }

    /**
     * Deletes a field layout.
     *
     * @param  FieldLayout  $layout  The field layout
     * @return bool Whether the field layout was deleted successfully
     */
    public function deleteLayout(FieldLayout $layout): bool
    {
        if (Event::hasListeners(FieldLayoutDeleting::class)) {
            Event::dispatch(new FieldLayoutDeleting($layout));
        }

        DB::table(Table::FIELDLAYOUTS)->softDelete($layout->id);

        if (Event::hasListeners(FieldLayoutDeleted::class)) {
            Event::dispatch(new FieldLayoutDeleted($layout));
        }

        // Clear caches
        $this->_layouts = null;

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

        return (bool) $affectedRows;
    }

    /**
     * Returns the current field version.
     */
    public function getFieldVersion(): ?string
    {
        $fieldVersion = Info::fetch()->fieldVersion;

        // If it doesn't start with `3@`, then it needs to be updated
        if ($fieldVersion === null || ! str_starts_with($fieldVersion, '3@')) {
            return null;
        }

        return $fieldVersion;
    }

    /**
     * Sets a new field version, so the CustomFieldBehavior class
     * will get regenerated on the next request.
     */
    public function updateFieldVersion(): void
    {
        // Make sure that CustomFieldBehavior has already been loaded,
        // so the field version change won't be detected until the next request
        class_exists(CustomFieldBehavior::class);

        Info::fetch()->update([
            'fieldVersion' => '3@'.Str::random(10),
        ]);
    }

    /**
     * Applies a field save to the database.
     */
    public function applyFieldSave(string $fieldUid, array $data, string $context): void
    {
        $fieldRecord = $this->_getFieldModel($fieldUid, true);
        $isNewField = ! $fieldRecord->exists;
        $oldSettings = $fieldRecord->getOriginal('settings');
        $oldField = ! $isNewField ? $this->getFieldById($fieldRecord->id) : null;

        // For control panel save requests, make sure we have all the custom data already saved on the object.
        $field = $this->_savingFields[$fieldUid] ?? null;

        if (Event::hasListeners(ApplyingFieldSave::class)) {
            Event::dispatch(new ApplyingFieldSave($oldField, $data));
        }

        DB::beginTransaction();

        try {
            // Track whether we should remove the field’s search indexes after save
            $searchable = $data['searchable'] ?? false;
            $deleteSearchIndexes = ! $isNewField && ! $searchable && $fieldRecord->searchable;

            // Clear the translation key format if not using a custom translation method
            if ($data['translationMethod'] !== Field::TRANSLATION_METHOD_CUSTOM) {
                $data['translationKeyFormat'] = null;
            }

            if (! empty($data['settings']) && is_array($data['settings'])) {
                $data['settings'] = ProjectConfigHelper::unpackAssociativeArrays($data['settings']);
            }

            $fieldRecord->uid = $fieldUid;
            $fieldRecord->name = $data['name'];
            $fieldRecord->handle = $data['handle'];
            $fieldRecord->context = $context;
            $fieldRecord->columnSuffix = $data['columnSuffix'] ?? null;
            $fieldRecord->instructions = $data['instructions'];
            $fieldRecord->searchable = (bool) $searchable;
            $fieldRecord->translationMethod = $data['translationMethod'];
            $fieldRecord->translationKeyFormat = $data['translationKeyFormat'];
            $fieldRecord->type = $data['type'];
            $fieldRecord->settings = $data['settings'] ?? null;

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

        // Tell the current CustomFieldBehavior class about the field
        CustomFieldBehavior::$fieldHandles[$fieldRecord->handle] = true;

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

        if (Event::hasListeners(FieldSaved::class)) {
            Event::dispatch(new FieldSaved($field, $isNewField));
        }

        // If we just dropped `searchable`, delete the field’s search indexes immediately.
        if ($deleteSearchIndexes) {
            DB::table(Table::SEARCHINDEX)
                ->where('attribute', 'field')
                ->where('fieldId', $field->id)
                ->delete();
        }

        // Invalidate all element caches
        Craft::$app->getElements()->invalidateAllCaches();
    }

    /**
     * Returns data for the Fields index page in the control panel.
     *
     *
     * @internal
     */
    public function getTableData(
        int $page,
        int $limit,
        ?string $searchTerm,
        string $orderBy = 'name',
        int $sortDir = SORT_ASC,
    ): array {
        $searchTerm = $searchTerm ? trim($searchTerm) : $searchTerm;

        $offset = ($page - 1) * $limit;
        $query = $this->_createFieldQuery()
            ->where('context', 'global');

        $sortDir = $sortDir === SORT_ASC ? 'asc' : 'desc';

        if ($orderBy === 'type') {
            $types = $this->getAllFieldTypes()->sortBy(
                /** @var class-string<FieldInterface> $class */
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

        $total = $query->count();

        $query->limit($limit);
        $query->offset($offset);

        $result = $query->get();

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
                    'icon' => Cp::iconSvg($field::icon()),
                ],
                'usages' => isset($usages[$field->id])
                    ? t('{count, number} {count, plural, =1{layout} other{layouts}}', [
                        'count' => count($usages[$field->id]),
                    ])
                    : null,
            ];
        }

        $pagination = AdminTable::paginationLinks($page, $total, $limit);

        return [$pagination, $tableData];
    }

    /**
     * Returns the array of sql "like" params to be used in the 'where' param for the query.
     */
    private function _getSearchParams(string $term): array
    {
        $searchParams = ['name', 'handle', 'instructions', 'type'];
        $searchQueries = [];

        if ($term === '') {
            return $searchQueries;
        }

        foreach ($searchParams as $param) {
            $searchQueries[] = [$param, 'like', '%'.$term.'%'];
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
                'fields.columnSuffix',
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
