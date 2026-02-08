<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string getFieldContext()
 * @method static void setFieldContext(string $fieldContext)
 * @method static \Illuminate\Support\Collection getAllFieldTypes()
 * @method static \Illuminate\Support\Collection getFieldTypesWithContent()
 * @method static \Illuminate\Support\Collection getCompatibleFieldTypes(\CraftCms\Cms\Field\Contracts\FieldInterface $field, bool $includeCurrent = true)
 * @method static bool areFieldTypesCompatible(string $fieldA, string $fieldB)
 * @method static \Illuminate\Support\Collection getNestedEntryFieldTypes()
 * @method static \Illuminate\Support\Collection getRelationalFieldTypes()
 * @method static \CraftCms\Cms\Field\Contracts\FieldInterface createField(string|array $config)
 * @method static \Illuminate\Support\Collection getAllFields(string|string[]|false|null $context = null)
 * @method static \Illuminate\Support\Collection getFieldsWithContent(string|string[]|false|null $context = null)
 * @method static \Illuminate\Support\Collection getFieldsWithoutContent(string|string[]|false|null $context = null)
 * @method static \Illuminate\Support\Collection getFieldsByType(string $type, string|string[]|false|null $context = null)
 * @method static \CraftCms\Cms\Field\Contracts\FieldInterface|null getFieldById(int $fieldId)
 * @method static \CraftCms\Cms\Field\Contracts\FieldInterface|null getFieldByUid(string $fieldUid)
 * @method static \CraftCms\Cms\Field\Contracts\FieldInterface|null getFieldByHandle(string $handle, string|string[]|false|null $context = null)
 * @method static bool doesFieldWithHandleExist(string $handle, string|null $context = null)
 * @method static array createFieldConfig(\CraftCms\Cms\Field\Contracts\FieldInterface $field)
 * @method static bool saveField(\CraftCms\Cms\Field\Contracts\FieldInterface $field, bool $runValidation = true)
 * @method static void prepFieldForSave(\CraftCms\Cms\Field\Contracts\FieldInterface $field)
 * @method static void handleChangedField(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event)
 * @method static bool deleteFieldById(int $fieldId)
 * @method static bool deleteField(\CraftCms\Cms\Field\Contracts\FieldInterface $field)
 * @method static void handleDeletedField(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event)
 * @method static void applyFieldDelete(string $fieldUid)
 * @method static void refreshFields()
 * @method static \Illuminate\Support\Collection findFieldUsages(\CraftCms\Cms\Field\Contracts\FieldInterface $field)
 * @method static \Illuminate\Support\Collection getAllLayouts()
 * @method static \craft\models\FieldLayout|null getLayoutById(int $layoutId, bool $withTrashed = false)
 * @method static \craft\models\FieldLayout|null getLayoutByUid(string $uid)
 * @method static \Illuminate\Support\Collection getLayoutsByIds(int[] $layoutIds)
 * @method static \craft\models\FieldLayout|null getLayoutByType(string $type, bool $create = true)
 * @method static \Illuminate\Support\Collection getLayoutsByType(string $type)
 * @method static \craft\models\FieldLayout createLayout(array $config)
 * @method static \craft\base\FieldLayoutElement createLayoutElement(array $config)
 * @method static \craft\models\FieldLayout assembleLayoutFromPost(string|null $namespace = null)
 * @method static bool saveLayout(\craft\models\FieldLayout $layout, bool $runValidation = true)
 * @method static bool deleteLayoutById(int|int[] $layoutId)
 * @method static bool deleteLayout(\craft\models\FieldLayout $layout)
 * @method static bool deleteLayoutsByType(string $type)
 * @method static bool restoreLayoutById(int $id)
 * @method static string|null getFieldVersion()
 * @method static void updateFieldVersion()
 * @method static void applyFieldSave(string $fieldUid, array $data, string $context)
 * @method static array allFieldHandles()
 * @method static array allGeneratedFieldHandles()
 * @method static bool isFieldHandle(string $handle)
 * @method static bool isGeneratedFieldHandle(string $handle)
 * @method static bool isKnownFieldHandle(string $handle)
 * @method static void invalidateCaches()
 *
 * @see \CraftCms\Cms\Field\Fields
 */
final class Fields extends Facade
{
    #[\Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Field\Fields::class;
    }
}
