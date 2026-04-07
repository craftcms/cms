<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static array<string, bool> allFieldHandles()
 * @method static array<string, bool> allGeneratedFieldHandles()
 * @method static bool isFieldHandle(string $handle)
 * @method static bool isGeneratedFieldHandle(string $handle)
 * @method static bool isKnownFieldHandle(string $handle)
 * @method static void invalidateCaches()
 * @method static string getFieldContext()
 * @method static void setFieldContext(string $fieldContext)
 * @method static \Illuminate\Support\Collection<string<\CraftCms\Cms\Field\Contracts\FieldInterface>> getAllFieldTypes()
 * @method static \Illuminate\Support\Collection getFieldTypesWithContent()
 * @method static \Illuminate\Support\Collection<string<\CraftCms\Cms\Field\Contracts\FieldInterface>> getCompatibleFieldTypes(\CraftCms\Cms\Field\Contracts\FieldInterface $field, bool $includeCurrent = true)
 * @method static bool areFieldTypesCompatible(string<\CraftCms\Cms\Field\Contracts\FieldInterface> $fieldA, string<\CraftCms\Cms\Field\Contracts\FieldInterface> $fieldB)
 * @method static \Illuminate\Support\Collection<string<\CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface>> getNestedEntryFieldTypes()
 * @method static \Illuminate\Support\Collection<string<\CraftCms\Cms\Field\BaseRelationField>> getRelationalFieldTypes()
 * @method static \CraftCms\Cms\Field\Contracts\FieldInterface createField(string<\CraftCms\Cms\Field\Contracts\FieldInterface>|array $config)
 * @method static \Illuminate\Support\Collection<\CraftCms\Cms\Field\Contracts\FieldInterface> getAllFields(string|string[]|false|null $context = null)
 * @method static \Illuminate\Support\Collection<\CraftCms\Cms\Field\Contracts\FieldInterface> getFieldsWithContent(string|string[]|false|null $context = null)
 * @method static \Illuminate\Support\Collection<\CraftCms\Cms\Field\Contracts\FieldInterface> getFieldsWithoutContent(string|string[]|false|null $context = null)
 * @method static \Illuminate\Support\Collection<\CraftCms\Cms\Field\Contracts\FieldInterface> getFieldsByType(string<\CraftCms\Cms\Field\Contracts\FieldInterface> $type, string|string[]|false|null $context = null)
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
 * @method static \Illuminate\Support\Collection<\CraftCms\Cms\FieldLayout\FieldLayout> findFieldUsages(\CraftCms\Cms\Field\Contracts\FieldInterface $field)
 * @method static \Illuminate\Support\Collection<\CraftCms\Cms\FieldLayout\FieldLayout> getAllLayouts()
 * @method static \CraftCms\Cms\FieldLayout\FieldLayout|null getLayoutById(int $layoutId, bool $withTrashed = false)
 * @method static \CraftCms\Cms\FieldLayout\FieldLayout|null getLayoutByUid(string $uid)
 * @method static \Illuminate\Support\Collection<\CraftCms\Cms\FieldLayout\FieldLayout> getLayoutsByIds(int[] $layoutIds)
 * @method static \CraftCms\Cms\FieldLayout\FieldLayout|null getLayoutByType(string<\craft\base\ElementInterface> $type, bool $create = true)
 * @method static \Illuminate\Support\Collection<\CraftCms\Cms\FieldLayout\FieldLayout> getLayoutsByType(string<\craft\base\ElementInterface> $type)
 * @method static \CraftCms\Cms\FieldLayout\FieldLayout createLayout(array $config)
 * @method static \CraftCms\Cms\FieldLayout\FieldLayoutElement createLayoutElement(array $config)
 * @method static \CraftCms\Cms\FieldLayout\FieldLayout assembleLayoutFromPost(string|null $namespace = null)
 * @method static bool saveLayout(\CraftCms\Cms\FieldLayout\FieldLayout $layout, bool $runValidation = true)
 * @method static bool deleteLayoutById(int|int[] $layoutId)
 * @method static bool deleteLayout(\CraftCms\Cms\FieldLayout\FieldLayout $layout)
 * @method static bool deleteLayoutsByType(string<\craft\base\ElementInterface> $type)
 * @method static bool restoreLayoutById(int $id)
 * @method static void applyFieldSave(string $fieldUid, array $data, string $context)
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
