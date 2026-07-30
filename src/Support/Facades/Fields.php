<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static array<array-key, mixed> allFieldHandles()
 * @method static array<array-key, mixed> allGeneratedFieldHandles()
 * @method static bool isFieldHandle(string $handle)
 * @method static bool isGeneratedFieldHandle(string $handle)
 * @method static bool isKnownFieldHandle(string $handle)
 * @method static void invalidateCaches()
 * @method static string getFieldContext()
 * @method static void setFieldContext(string $fieldContext)
 * @method static \Illuminate\Support\Collection<array-key, mixed> getAllFieldTypes()
 * @method static \Illuminate\Support\Collection<array-key, mixed> getFieldTypesWithContent()
 * @method static \Illuminate\Support\Collection<array-key, mixed> getCompatibleFieldTypes(\CraftCms\Cms\Field\Contracts\FieldInterface $field, bool $includeCurrent = true)
 * @method static bool areFieldTypesCompatible(string $fieldA, string $fieldB)
 * @method static \Illuminate\Support\Collection<array-key, mixed> getNestedEntryFieldTypes()
 * @method static \Illuminate\Support\Collection<array-key, mixed> getRelationalFieldTypes()
 * @method static \CraftCms\Cms\Field\Contracts\FieldInterface createField(string|array<array-key, mixed> $config)
 * @method static \Illuminate\Support\Collection<array-key, mixed> getAllFields(string|string[]|false|null $context = null)
 * @method static \Illuminate\Support\Collection<array-key, mixed> getFieldsWithContent(string|string[]|false|null $context = null)
 * @method static \Illuminate\Support\Collection<array-key, mixed> getFieldsWithoutContent(string|string[]|false|null $context = null)
 * @method static \Illuminate\Support\Collection<array-key, mixed> getFieldsByType(string $type, string|string[]|false|null $context = null)
 * @method static \CraftCms\Cms\Field\Contracts\FieldInterface|null getFieldById(int $fieldId)
 * @method static \CraftCms\Cms\Field\Contracts\FieldInterface|null getFieldByUid(string $fieldUid)
 * @method static \CraftCms\Cms\Field\Contracts\FieldInterface|null getFieldByHandle(string $handle, string|string[]|false|null $context = null)
 * @method static bool doesFieldWithHandleExist(string $handle, string|null $context = null)
 * @method static array<array-key, mixed> createFieldConfig(\CraftCms\Cms\Field\Contracts\FieldInterface $field)
 * @method static bool saveField(\CraftCms\Cms\Field\Contracts\FieldInterface $field, bool $runValidation = true)
 * @method static void prepFieldForSave(\CraftCms\Cms\Field\Contracts\FieldInterface $field)
 * @method static void handleChangedField(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event)
 * @method static bool deleteFieldById(int $fieldId)
 * @method static bool deleteField(\CraftCms\Cms\Field\Contracts\FieldInterface $field)
 * @method static void handleDeletedField(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event)
 * @method static void applyFieldDelete(string $fieldUid)
 * @method static void refreshFields()
 * @method static \Illuminate\Support\Collection<array-key, mixed> findFieldUsages(\CraftCms\Cms\Field\Contracts\FieldInterface $field)
 * @method static \CraftCms\Cms\Field\Data\FieldMergeResult merge(\CraftCms\Cms\Field\Contracts\FieldInterface&\CraftCms\Cms\Field\Contracts\MergeableFieldInterface $persistingField, \CraftCms\Cms\Field\Contracts\FieldInterface&\CraftCms\Cms\Field\Contracts\MergeableFieldInterface $outgoingField)
 * @method static \Illuminate\Support\Collection<array-key, mixed> getAllLayouts()
 * @method static \CraftCms\Cms\FieldLayout\FieldLayout|null getLayoutById(int $layoutId, bool $withTrashed = false)
 * @method static \CraftCms\Cms\FieldLayout\FieldLayout|null getLayoutByUid(string $uid)
 * @method static \Illuminate\Support\Collection<array-key, mixed> getLayoutsByIds(int[] $layoutIds)
 * @method static \CraftCms\Cms\FieldLayout\FieldLayout|null getLayoutByType(string $type, bool $create = true)
 * @method static \Illuminate\Support\Collection<array-key, mixed> getLayoutsByType(string $type)
 * @method static \CraftCms\Cms\FieldLayout\FieldLayout createLayout(array<array-key, mixed>|string $config)
 * @method static \CraftCms\Cms\FieldLayout\FieldLayoutElement createLayoutElement(array<array-key, mixed> $config)
 * @method static \CraftCms\Cms\FieldLayout\FieldLayout assembleLayoutFromPost(string|null $namespace = null)
 * @method static bool saveLayout(\CraftCms\Cms\FieldLayout\FieldLayout $layout, bool $runValidation = true)
 * @method static bool deleteLayoutById(int|int[] $layoutId, bool $hardDelete = false)
 * @method static bool deleteLayout(\CraftCms\Cms\FieldLayout\FieldLayout $layout, bool $hardDelete = false)
 * @method static bool deleteLayoutsByType(string $type)
 * @method static bool restoreLayoutById(int $id)
 * @method static void applyFieldSave(string $fieldUid, array<array-key, mixed> $data, string $context)
 *
 * @see \CraftCms\Cms\Field\Fields
 */
class Fields extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Field\Fields::class;
    }
}
