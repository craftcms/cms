<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static array allFieldHandles()
 * @method static array allGeneratedFieldHandles()
 * @method static bool isFieldHandle(string $handle)
 * @method static bool isGeneratedFieldHandle(string $handle)
 * @method static bool isKnownFieldHandle(string $handle)
 * @method static void invalidateCaches()
 * @method static string getFieldContext()
 * @method static void setFieldContext(string $fieldContext)
 * @method static Collection getAllFieldTypes()
 * @method static Collection getFieldTypesWithContent()
 * @method static Collection getCompatibleFieldTypes(FieldInterface $field, bool $includeCurrent = true)
 * @method static bool areFieldTypesCompatible(string $fieldA, string $fieldB)
 * @method static Collection getNestedEntryFieldTypes()
 * @method static Collection getRelationalFieldTypes()
 * @method static FieldInterface createField(string|array $config)
 * @method static Collection getAllFields(string|string[]|false|null $context = null)
 * @method static Collection getFieldsWithContent(string|string[]|false|null $context = null)
 * @method static Collection getFieldsWithoutContent(string|string[]|false|null $context = null)
 * @method static Collection getFieldsByType(string $type, string|string[]|false|null $context = null)
 * @method static FieldInterface|null getFieldById(int $fieldId)
 * @method static FieldInterface|null getFieldByUid(string $fieldUid)
 * @method static FieldInterface|null getFieldByHandle(string $handle, string|string[]|false|null $context = null)
 * @method static bool doesFieldWithHandleExist(string $handle, string|null $context = null)
 * @method static array createFieldConfig(FieldInterface $field)
 * @method static bool saveField(FieldInterface $field, bool $runValidation = true)
 * @method static void prepFieldForSave(FieldInterface $field)
 * @method static void handleChangedField(ConfigEvent $event)
 * @method static bool deleteFieldById(int $fieldId)
 * @method static bool deleteField(FieldInterface $field)
 * @method static void handleDeletedField(ConfigEvent $event)
 * @method static void applyFieldDelete(string $fieldUid)
 * @method static void refreshFields()
 * @method static Collection findFieldUsages(FieldInterface $field)
 * @method static Collection getAllLayouts()
 * @method static FieldLayout|null getLayoutById(int $layoutId, bool $withTrashed = false)
 * @method static FieldLayout|null getLayoutByUid(string $uid)
 * @method static Collection getLayoutsByIds(int[] $layoutIds)
 * @method static FieldLayout|null getLayoutByType(string $type, bool $create = true)
 * @method static Collection getLayoutsByType(string $type)
 * @method static FieldLayout createLayout(array $config)
 * @method static FieldLayoutElement createLayoutElement(array $config)
 * @method static FieldLayout assembleLayoutFromPost(string|null $namespace = null)
 * @method static bool saveLayout(FieldLayout $layout, bool $runValidation = true)
 * @method static bool deleteLayoutById(int|int[] $layoutId)
 * @method static bool deleteLayout(FieldLayout $layout)
 * @method static bool deleteLayoutsByType(string $type)
 * @method static bool restoreLayoutById(int $id)
 * @method static void applyFieldSave(string $fieldUid, array $data, string $context)
 *
 * @see \CraftCms\Cms\Field\Fields
 */
final class Fields extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Field\Fields::class;
    }
}
