<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\ElementInterface;
use craft\events\ApplyFieldSaveEvent;
use craft\events\DefineCompatibleFieldTypesEvent;
use craft\events\FieldEvent;
use craft\events\LocateUploadedFilesEvent;
use craft\events\RegisterComponentTypesEvent;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
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
use CraftCms\Cms\Field\Events\LocateUploadedFiles;
use CraftCms\Cms\Field\Events\RegisterFieldTypes;
use CraftCms\Cms\Field\Events\RegisterNestedEntryFieldTypes;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Throwable;
use yii\base\Component;
use yii\base\Exception;
use yii\web\BadRequestHttpException;

/**
 * Fields service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getFields()|`Craft::$app->getFields()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Fields} instead.
 */
class Fields extends Component
{
    /**
     * @event RegisterComponentTypesEvent The event that is triggered when registering field types.
     *
     * Field types must implement [[FieldInterface]]. [[Field]] provides a base implementation.
     *
     * See [Field Types](https://craftcms.com/docs/5.x/extend/field-types.html) for documentation on creating field types.
     * ---
     * ```php
     * use craft\events\RegisterComponentTypesEvent;
     * use craft\services\Fields;
     * use yii\base\Event;
     *
     * Event::on(Fields::class,
     *     Fields::EVENT_REGISTER_FIELD_TYPES,
     *     function(RegisterComponentTypesEvent $event) {
     *         $event->types[] = MyFieldType::class;
     *     }
     * );
     * ```
     */
    public const EVENT_REGISTER_FIELD_TYPES = 'registerFieldTypes';

    /**
     * @event RegisterComponentTypesEvent The event that is triggered when registering field types which manage nested entries.
     *
     * These field types must implement [[ElementContainerFieldInterface]].
     *
     * @since 5.0.0
     */
    public const EVENT_REGISTER_NESTED_ENTRY_FIELD_TYPES = 'registerNestedEntryFieldTypes';

    /**
     * @event DefineCompatibleFieldTypesEvent The event that is triggered when defining the compatible field types for a field.
     * @see getCompatibleFieldTypes()
     * @since 4.5.7
     */
    public const EVENT_DEFINE_COMPATIBLE_FIELD_TYPES = 'defineCompatibleFieldTypes';

    /**
     * @event FieldEvent The event that is triggered before a field is saved.
     */
    public const EVENT_BEFORE_SAVE_FIELD = 'beforeSaveField';

    /**
     * @event ApplyFieldSaveEvent The event that is triggered before a field save is applied to the database.
     * @since 5.5.0
     */
    public const EVENT_BEFORE_APPLY_FIELD_SAVE = 'beforeApplyFieldSave';

    /**
     * @event FieldEvent The event that is triggered after a field is saved.
     */
    public const EVENT_AFTER_SAVE_FIELD = 'afterSaveField';

    /**
     * @event FieldEvent The event that is triggered before a field is deleted.
     */
    public const EVENT_BEFORE_DELETE_FIELD = 'beforeDeleteField';

    /**
     * @event FieldEvent The event that is triggered before a field delete is applied to the database.
     * @since 3.1.0
     */
    public const EVENT_BEFORE_APPLY_FIELD_DELETE = 'beforeApplyFieldDelete';

    /**
     * @event FieldEvent The event that is triggered after a field is deleted.
     */
    public const EVENT_AFTER_DELETE_FIELD = 'afterDeleteField';

    /**
     * @event FieldLayoutEvent The event that is triggered before a field layout is saved.
     */
    public const EVENT_BEFORE_SAVE_FIELD_LAYOUT = 'beforeSaveFieldLayout';

    /**
     * @event FieldLayoutEvent The event that is triggered after a field layout is saved.
     */
    public const EVENT_AFTER_SAVE_FIELD_LAYOUT = 'afterSaveFieldLayout';

    /**
     * @event FieldLayoutEvent The event that is triggered before a field layout is deleted.
     */
    public const EVENT_BEFORE_DELETE_FIELD_LAYOUT = 'beforeDeleteFieldLayout';

    /**
     * @event FieldLayoutEvent The event that is triggered after a field layout is deleted.
     */
    public const EVENT_AFTER_DELETE_FIELD_LAYOUT = 'afterDeleteFieldLayout';

    /**
     * @var string The active field context
     * @since 5.0.0
     */
    public string $fieldContext {
        get => app(\CraftCms\Cms\Field\Fields::class)->fieldContext;
    set(string $value) {
            app(\CraftCms\Cms\Field\Fields::class)->fieldContext = $value;
        }
    }

    // Fields
    // -------------------------------------------------------------------------

    /**
     * Returns all available field type classes.
     *
     * @return string[] The available field type classes
     * @phpstan-return class-string<FieldInterface>[]
     */
    public function getAllFieldTypes(): array
    {
        return app(\CraftCms\Cms\Field\Fields::class)->getAllFieldTypes()->all();
    }

    /**
     * Returns all field types that have a column in the content table.
     *
     * @return string[] The field type classes
     * @phpstan-return class-string<FieldInterface>[]
     */
    public function getFieldTypesWithContent(): array
    {
        return app(\CraftCms\Cms\Field\Fields::class)->getFieldTypesWithContent()->all();
    }

    /**
     * Returns all field types whose column types are considered compatible with a given field.
     *
     * @param FieldInterface $field The current field to base compatible fields on
     * @param bool $includeCurrent Whether $field's class should be included
     *
     * @return string[] The compatible field type classes
     * @phpstan-return class-string<FieldInterface>[]
     */
    public function getCompatibleFieldTypes(FieldInterface $field, bool $includeCurrent = true): array
    {
        return app(\CraftCms\Cms\Field\Fields::class)->getCompatibleFieldTypes($field, $includeCurrent)->all();
    }

    /**
     * Returns whether the two given field types are considered compatible with each other.
     *
     * @param class-string<FieldInterface> $fieldA
     * @param class-string<FieldInterface> $fieldB
     *
     * @return bool
     * @since 5.3.0
     */
    public function areFieldTypesCompatible(string $fieldA, string $fieldB): bool
    {
        return app(\CraftCms\Cms\Field\Fields::class)->areFieldTypesCompatible($fieldA, $fieldB);
    }

    /**
     * Returns all field types which manage nested entries.
     *
     * @return string[] The field type classes which manage nested entries
     * @phpstan-return class-string<ElementContainerFieldInterface>[]
     */
    public function getNestedEntryFieldTypes(): array
    {
        return app(\CraftCms\Cms\Field\Fields::class)->getNestedEntryFieldTypes()->all();
    }

    /**
     * Returns all available relational field type classes.
     *
     * @return string[] The available relational field type classes
     * @phpstan-return class-string<BaseRelationField>[]
     * @since 5.1.6
     */
    public function getRelationalFieldTypes(): array
    {
        return app(\CraftCms\Cms\Field\Fields::class)->getRelationalFieldTypes()->all();
    }

    /**
     * Creates a field with a given config.
     *
     * @template T of FieldInterface
     * @param class-string<T>|array $config The field’s class name, or its config, with a `type` value and optionally a `settings` value
     *
     * @phpstan-param class-string<T>|array{type:class-string<T>,id?:int|string,uid?:string} $config
     * @return T The field
     */
    public function createField(mixed $config): FieldInterface
    {
        return app(\CraftCms\Cms\Field\Fields::class)->createField($config);
    }

    /**
     * Returns all fields within a field context(s).
     *
     * @param string|string[]|false|null $context The field context(s) to fetch fields from. Defaults to [[\craft\services\Fields::$fieldContext]].
     * Set to `false` to get all fields regardless of context.
     *
     * @return FieldInterface[] The fields
     */
    public function getAllFields(mixed $context = null): array
    {
        return app(\CraftCms\Cms\Field\Fields::class)->getAllFields($context)->all();
    }

    /**
     * Returns all fields that store content in the `elements_sites.content` table.
     *
     * @param string|string[]|false|null $context The field context(s) to fetch fields from. Defaults to [[\craft\services\Fields::$fieldContext]].
     * Set to `false` to get all fields regardless of context.
     *
     * @return FieldInterface[] The fields
     */
    public function getFieldsWithContent(mixed $context = null): array
    {
        return app(\CraftCms\Cms\Field\Fields::class)->getFieldsWithContent($context)->values()->all();
    }

    /**
     * Returns all fields that don’t store content in the `elements_sites.content` table.
     *
     * @param string|string[]|false|null $context The field context(s) to fetch fields from. Defaults to [[\craft\services\Fields::$fieldContext]].
     * Set to `false` to get all fields regardless of context.
     *
     * @return FieldInterface[] The fields
     * @since 4.3.2
     */
    public function getFieldsWithoutContent(mixed $context = null): array
    {
        return app(\CraftCms\Cms\Field\Fields::class)->getFieldsWithoutContent($context)->values()->all();
    }

    /**
     * Returns all fields of a certain type.
     *
     * @param class-string<FieldInterface> $type The field type
     * @param string|string[]|false|null $context The field context(s) to fetch fields from. Defaults to [[\craft\services\Fields::$fieldContext]].
     * Set to `false` to get all fields regardless of context.
     *
     * @return FieldInterface[] The fields
     * @since 4.4.0
     */
    public function getFieldsByType(string $type, mixed $context = null): array
    {
        return app(\CraftCms\Cms\Field\Fields::class)->getFieldsByType($type, $context)->values()->all();
    }

    /**
     * Returns a field by its ID.
     *
     * @param int $fieldId The field’s ID
     *
     * @return FieldInterface|null The field, or null if it doesn’t exist
     */
    public function getFieldById(int $fieldId): ?FieldInterface
    {
        return app(\CraftCms\Cms\Field\Fields::class)->getFieldById($fieldId);
    }

    /**
     * Returns a field by its UID.
     *
     * @param string $fieldUid The field’s UID
     *
     * @return FieldInterface|null The field, or null if it doesn’t exist
     */
    public function getFieldByUid(string $fieldUid): ?FieldInterface
    {
        return app(\CraftCms\Cms\Field\Fields::class)->getFieldByUid($fieldUid);
    }

    /**
     * Returns a field by its handle and optional context.
     *
     * ---
     *
     * ```php
     * $body = app(Fields::class)->getFieldByHandle('body');
     * ```
     * ```twig
     * {% set body = craft.fields.getFieldByHandle('body') %}
     * {{ body.instructions }}
     * ```
     *
     * @param string $handle The field’s handle
     * @param string|string[]|false|null $context The field context(s) to fetch fields from. Defaults to [[\craft\services\Fields::$fieldContext]].
     * Set to `false` to get all fields regardless of context.
     *
     * @return FieldInterface|null The field, or null if it doesn’t exist
     */
    public function getFieldByHandle(string $handle, mixed $context = null): ?FieldInterface
    {
        return app(\CraftCms\Cms\Field\Fields::class)->getFieldByHandle($handle, $context);
    }

    /**
     * Returns whether a field exists with a given handle and context.
     *
     * @param string $handle The field handle
     * @param string|null $context The field context (defauts to [[\craft\services\Fields::$fieldContext]])
     *
     * @return bool Whether a field with that handle exists
     */
    public function doesFieldWithHandleExist(string $handle, ?string $context = null): bool
    {
        return app(\CraftCms\Cms\Field\Fields::class)->doesFieldWithHandleExist($handle, $context);
    }

    /**
     * Returns the config for the given field.
     *
     * @param FieldInterface $field
     *
     * @return array
     * @since 3.1.0
     */
    public function createFieldConfig(FieldInterface $field): array
    {
        return app(\CraftCms\Cms\Field\Fields::class)->createFieldConfig($field);
    }

    /**
     * Saves a field.
     *
     * @param FieldInterface $field The Field to be saved
     * @param bool $runValidation Whether the field should be validated
     *
     * @return bool Whether the field was saved successfully
     * @throws Throwable if reasons
     */
    public function saveField(FieldInterface $field, bool $runValidation = true): bool
    {
        return app(\CraftCms\Cms\Field\Fields::class)->saveField($field, $runValidation);
    }

    /**
     * Preps a field to be saved.
     *
     * @param FieldInterface $field
     *
     * @since 3.1.2
     */
    public function prepFieldForSave(FieldInterface $field): void
    {
        app(\CraftCms\Cms\Field\Fields::class)->prepFieldForSave($field);
    }

    /**
     * Handle field changes.
     *
     * @param ConfigEvent $event
     *
     * @throws Throwable
     */
    public function handleChangedField(ConfigEvent $event): void
    {
        app(\CraftCms\Cms\Field\Fields::class)->handleChangedField($event);
    }

    /**
     * Deletes a field by its ID.
     *
     * @param int $fieldId The field’s ID
     *
     * @return bool Whether the field was deleted successfully
     */
    public function deleteFieldById(int $fieldId): bool
    {
        return app(\CraftCms\Cms\Field\Fields::class)->deleteFieldById($fieldId);
    }

    /**
     * Deletes a field.
     *
     * @param FieldInterface $field The field
     *
     * @return bool Whether the field was deleted successfully
     * @throws Throwable if reasons
     */
    public function deleteField(FieldInterface $field): bool
    {
        return app(\CraftCms\Cms\Field\Fields::class)->deleteField($field);
    }

    /**
     * Handle a field getting deleted.
     *
     * @param ConfigEvent $event
     */
    public function handleDeletedField(ConfigEvent $event): void
    {
        app(\CraftCms\Cms\Field\Fields::class)->handleDeletedField($event);
    }

    /**
     * Applies a field delete to the database.
     *
     * @param string $fieldUid
     *
     * @throws Throwable if database error
     * @since 3.1.0
     */
    public function applyFieldDelete(string $fieldUid): void
    {
        app(\CraftCms\Cms\Field\Fields::class)->applyFieldDelete($fieldUid);
    }

    /**
     * Refreshes the internal field cache.
     *
     * This should be called whenever a field is updated or deleted directly in
     * the database, rather than going through this service.
     *
     * @since 3.0.20
     */
    public function refreshFields(): void
    {
        app(\CraftCms\Cms\Field\Fields::class)->refreshFields();
    }

    /**
     * Returns all the field layouts that contain the given field.
     *
     * @param FieldInterface $field
     *
     * @return FieldLayout[]
     * @since 5.0.0
     */
    public function findFieldUsages(FieldInterface $field): array
    {
        return app(\CraftCms\Cms\Field\Fields::class)->findFieldUsages($field)->all();
    }

    // Layouts
    // -------------------------------------------------------------------------

    /**
     * Returns all saved field layouts.
     *
     * @return \CraftCms\Cms\FieldLayout\FieldLayout[]
     * @since 5.0.0
     */
    public function getAllLayouts(): array
    {
        return app(\CraftCms\Cms\Field\Fields::class)->getAllLayouts()->all();
    }

    /**
     * Returns a field layout by its ID.
     *
     * @param int $layoutId The field layout’s ID
     * @param bool $withTrashed Whether to return the field layout even if it’s soft-deleted
     *
     * @return \CraftCms\Cms\FieldLayout\FieldLayout|null The field layout, or null if it doesn’t exist
     */
    public function getLayoutById(int $layoutId, bool $withTrashed = false): ?FieldLayout
    {
        return app(\CraftCms\Cms\Field\Fields::class)->getLayoutById($layoutId, $withTrashed);
    }

    /**
     * Returns a field layout by its UUID.
     *
     * @param string $uid The field layout’s UUID
     *
     * @return FieldLayout|null The field layout, or null if it doesn’t exist
     */
    public function getLayoutByUid(string $uid): ?FieldLayout
    {
        return app(\CraftCms\Cms\Field\Fields::class)->getLayoutByUid($uid);
    }

    /**
     * Returns field layouts by their IDs.
     *
     * @param int[] $layoutIds The field layouts’ IDs
     *
     * @return \CraftCms\Cms\FieldLayout\FieldLayout[] The field layouts
     * @since 3.7.27
     */
    public function getLayoutsByIds(array $layoutIds): array
    {
        return app(\CraftCms\Cms\Field\Fields::class)->getLayoutsByIds($layoutIds)->all();
    }

    /**
     * Returns a field layout by its associated element type.
     *
     * @param class-string<ElementInterface> $type The associated element type
     * @param bool $create Whether to create a field layout if one doesn’t exist
     *
     * @return \CraftCms\Cms\FieldLayout\FieldLayout|null The field layout
     */
    public function getLayoutByType(string $type, bool $create = true): ?FieldLayout
    {
        return app(\CraftCms\Cms\Field\Fields::class)->getLayoutByType($type, $create);
    }

    /**
     * Returns all of the field layouts associated with a given element type.
     *
     * @param class-string<ElementInterface> $type
     *
     * @return FieldLayout[] The field layouts
     * @since 3.5.0
     */
    public function getLayoutsByType(string $type): array
    {
        return app(\CraftCms\Cms\Field\Fields::class)->getLayoutsByType($type)->all();
    }

    /**
     * Creates a field layout from the given config.
     *
     * @param array $config
     *
     * @return \CraftCms\Cms\FieldLayout\FieldLayout
     * @since 4.0.0
     */
    public function createLayout(array $config): FieldLayout
    {
        return app(\CraftCms\Cms\Field\Fields::class)->createLayout($config);
    }

    /**
     * Creates a field layout element instance from its config.
     *
     * @template T of \CraftCms\Cms\FieldLayout\FieldLayoutElement
     * @param array $config
     *
     * @phpstan-param array{type:class-string<T>} $config
     * @return T
     * @throws InvalidArgumentException if `$config['type']` does not implement [[FieldLayoutElement]]
     * @since 3.5.0
     */
    public function createLayoutElement(array $config): FieldLayoutElement
    {
        return app(\CraftCms\Cms\Field\Fields::class)->createLayoutElement($config);
    }

    /**
     * Assembles a field layout from post data.
     *
     * @param string|null $namespace The namespace that the form data was posted in, if any
     *
     * @return FieldLayout The field layout
     * @throws BadRequestHttpException
     */
    public function assembleLayoutFromPost(?string $namespace = null): FieldLayout
    {
        return app(\CraftCms\Cms\Field\Fields::class)->assembleLayoutFromPost($namespace);
    }

    /**
     * Saves a field layout.
     *
     * @param \CraftCms\Cms\FieldLayout\FieldLayout $layout The field layout
     * @param bool $runValidation Whether the layout should be validated
     *
     * @return bool Whether the field layout was saved successfully
     * @throws Exception if $layout->id is set to an invalid layout ID
     */
    public function saveLayout(FieldLayout $layout, bool $runValidation = true): bool
    {
        try {
            return app(\CraftCms\Cms\Field\Fields::class)->saveLayout($layout, $runValidation);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Deletes a field layout(s) by its ID.
     *
     * @param int|int[] $layoutId The field layout’s ID
     *
     * @return bool Whether the field layout was deleted successfully
     */
    public function deleteLayoutById(array|int $layoutId): bool
    {
        return app(\CraftCms\Cms\Field\Fields::class)->deleteLayoutById($layoutId);
    }

    /**
     * Deletes a field layout.
     *
     * @param \CraftCms\Cms\FieldLayout\FieldLayout $layout The field layout
     *
     * @return bool Whether the field layout was deleted successfully
     */
    public function deleteLayout(FieldLayout $layout): bool
    {
        return app(\CraftCms\Cms\Field\Fields::class)->deleteLayout($layout);
    }

    /**
     * Deletes field layouts associated with a given element type.
     *
     * @param class-string<ElementInterface> $type The element type
     *
     * @return bool Whether the field layouts were deleted successfully
     */
    public function deleteLayoutsByType(string $type): bool
    {
        return app(\CraftCms\Cms\Field\Fields::class)->deleteLayoutsByType($type);
    }

    /**
     * Restores a field layout by its ID.
     *
     * @param int $id The field layout’s ID
     *
     * @return bool Whether the layout was restored successfully
     * @since 3.1.0
     */
    public function restoreLayoutById(int $id): bool
    {
        return app(\CraftCms\Cms\Field\Fields::class)->restoreLayoutById($id);
    }

    /**
     * Returns the current field version.
     *
     * @return string|null
     * @since 3.7.21
     */
    public function getFieldVersion(): ?string
    {
        return null;
    }

    /**
     * Sets a new field version, so the CustomFieldBehavior class
     * will get regenerated on the next request.
     */
    public function updateFieldVersion(): void
    {
        // Not implemented
    }

    /**
     * Applies a field save to the database.
     *
     * @param string $fieldUid
     * @param array $data
     * @param string $context
     *
     * @since 3.1.0
     */
    public function applyFieldSave(string $fieldUid, array $data, string $context): void
    {
        app(\CraftCms\Cms\Field\Fields::class)->applyFieldSave($fieldUid, $data, $context);
    }


    /**
     * Returns data for the Fields index page in the control panel.
     *
     * @param int $page
     * @param int $limit
     * @param string|null $searchTerm
     * @param string $orderBy
     * @param int $sortDir
     *
     * @return array
     * @since 5.0.0
     * @internal
     */
    public function getTableData(
        int $page,
        int $limit,
        ?string $searchTerm,
        string $orderBy = 'name',
        int $sortDir = SORT_ASC,
    ): array {
        return app(\CraftCms\Cms\Field\Fields::class)->getTableData($page, $limit, $searchTerm, $orderBy, $sortDir);
    }

    public static function registerEvents(): void
    {
        Event::listen(RegisterFieldTypes::class, function(RegisterFieldTypes $event) {
            if (Craft::$app->getFields()->hasEventHandlers(self::EVENT_REGISTER_FIELD_TYPES)) {
                $yiiEvent = new RegisterComponentTypesEvent(['types' => $event->types->all()]);
                Craft::$app->getFields()->trigger(self::EVENT_REGISTER_FIELD_TYPES, $yiiEvent);
                $event->types = new Collection($yiiEvent->types);
            }
        });

        Event::listen(DefineCompatibleFieldTypes::class, function(DefineCompatibleFieldTypes $event) {
            if (Craft::$app->getFields()->hasEventHandlers(self::EVENT_DEFINE_COMPATIBLE_FIELD_TYPES)) {
                $yiiEvent = new DefineCompatibleFieldTypesEvent(['field' => $event->field, 'compatibleTypes' => $event->compatibleTypes->all()]);
                Craft::$app->getFields()->trigger(self::EVENT_DEFINE_COMPATIBLE_FIELD_TYPES, $yiiEvent);
                $event->compatibleTypes = new Collection($yiiEvent->compatibleTypes);
            }
        });

        Event::listen(RegisterNestedEntryFieldTypes::class, function(RegisterNestedEntryFieldTypes $event) {
            if (Craft::$app->getFields()->hasEventHandlers(self::EVENT_REGISTER_NESTED_ENTRY_FIELD_TYPES)) {
                $yiiEvent = new RegisterComponentTypesEvent(['types' => $event->types->all()]);
                Craft::$app->getFields()->trigger(self::EVENT_REGISTER_NESTED_ENTRY_FIELD_TYPES, $yiiEvent);
                $event->types = new Collection($yiiEvent->types);
            }
        });

        Event::listen(FieldSaving::class, function(FieldSaving $event) {
            if (Craft::$app->getFields()->hasEventHandlers(self::EVENT_BEFORE_SAVE_FIELD)) {
                $yiiEvent = new FieldEvent(['field' => $event->field, 'isNew' => $event->isNew]);
                Craft::$app->getFields()->trigger(self::EVENT_BEFORE_SAVE_FIELD, $yiiEvent);
                $event->field = $yiiEvent->field;
            }
        });

        Event::listen(FieldDeleting::class, function(FieldDeleting $event) {
            if (Craft::$app->getFields()->hasEventHandlers(self::EVENT_BEFORE_DELETE_FIELD)) {
                $yiiEvent = new FieldEvent(['field' => $event->field]);
                Craft::$app->getFields()->trigger(self::EVENT_BEFORE_DELETE_FIELD, $yiiEvent);
                $event->field = $yiiEvent->field;
            }
        });

        Event::listen(ApplyingFieldDelete::class, function(ApplyingFieldDelete $event) {
            if (Craft::$app->getFields()->hasEventHandlers(self::EVENT_BEFORE_APPLY_FIELD_DELETE)) {
                $yiiEvent = new FieldEvent(['field' => $event->field]);
                Craft::$app->getFields()->trigger(self::EVENT_BEFORE_APPLY_FIELD_DELETE, $yiiEvent);
                $event->field = $yiiEvent->field;
            }
        });

        Event::listen(FieldDeleted::class, function(FieldDeleted $event) {
            if (Craft::$app->getFields()->hasEventHandlers(self::EVENT_AFTER_DELETE_FIELD)) {
                $yiiEvent = new FieldEvent(['field' => $event->field]);
                Craft::$app->getFields()->trigger(self::EVENT_AFTER_DELETE_FIELD, $yiiEvent);
            }
        });

        Event::listen(FieldSaved::class, function(FieldSaved $event) {
            if (Craft::$app->getFields()->hasEventHandlers(self::EVENT_AFTER_SAVE_FIELD)) {
                $yiiEvent = new FieldEvent(['field' => $event->field, 'isNew' => $event->isNew]);
                Craft::$app->getFields()->trigger(self::EVENT_AFTER_SAVE_FIELD, $yiiEvent);
            }
        });

        Event::listen(FieldLayoutSaving::class, function(FieldLayoutSaving $event) {
            if (Craft::$app->getFields()->hasEventHandlers(self::EVENT_BEFORE_SAVE_FIELD_LAYOUT)) {
                $yiiEvent = new FieldEvent(['layout' => $event->layout, 'isNew' => $event->isNew]);
                Craft::$app->getFields()->trigger(self::EVENT_BEFORE_SAVE_FIELD_LAYOUT, $yiiEvent);
            }
        });

        Event::listen(FieldLayoutSaved::class, function(FieldLayoutSaved $event) {
            if (Craft::$app->getFields()->hasEventHandlers(self::EVENT_AFTER_SAVE_FIELD_LAYOUT)) {
                $yiiEvent = new FieldEvent(['layout' => $event->layout, 'isNew' => $event->isNew]);
                Craft::$app->getFields()->trigger(self::EVENT_AFTER_SAVE_FIELD_LAYOUT, $yiiEvent);
            }
        });

        Event::listen(FieldLayoutDeleting::class, function(FieldLayoutDeleting $event) {
            if (Craft::$app->getFields()->hasEventHandlers(self::EVENT_BEFORE_DELETE_FIELD_LAYOUT)) {
                $yiiEvent = new FieldEvent(['layout' => $event->layout]);
                Craft::$app->getFields()->trigger(self::EVENT_BEFORE_DELETE_FIELD_LAYOUT, $yiiEvent);
            }
        });

        Event::listen(FieldLayoutDeleted::class, function(FieldLayoutDeleted $event) {
            if (Craft::$app->getFields()->hasEventHandlers(self::EVENT_AFTER_DELETE_FIELD_LAYOUT)) {
                $yiiEvent = new FieldEvent(['layout' => $event->layout]);
                Craft::$app->getFields()->trigger(self::EVENT_AFTER_DELETE_FIELD_LAYOUT, $yiiEvent);
            }
        });

        Event::listen(ApplyingFieldSave::class, function(ApplyingFieldSave $event) {
            if (Craft::$app->getFields()->hasEventHandlers(self::EVENT_BEFORE_APPLY_FIELD_SAVE)) {
                Craft::$app->getFields()->trigger(self::EVENT_BEFORE_APPLY_FIELD_SAVE, new ApplyFieldSaveEvent([
                    'field' => $event->field,
                    'config' => $event->config,
                ]));
            }
        });

        \CraftCms\Cms\Field\Assets::listen(\CraftCms\Cms\Field\Assets::EVENT_LOCATE_UPLOADED_FILES, function(LocateUploadedFiles $event) {
            $yiiEvent = new LocateUploadedFilesEvent([
                'element' => $event->element,
                'files' => $event->files,
                'sender' => $event->field,
            ]);

            \craft\base\Event::trigger(
                \craft\fields\Assets::class,
                \CraftCms\Cms\Field\Assets::EVENT_LOCATE_UPLOADED_FILES,
                $yiiEvent,
            );
        });
    }
}
