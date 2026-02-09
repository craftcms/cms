<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use craft\errors\InvalidFieldException;
use craft\web\UploadedFile;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Elements\ContentBlock;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\FieldLayout\FieldLayout;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use UnitEnum;
use yii\base\InvalidConfigException;

/**
 * HasCustomFields provides custom field handling for elements.
 *
 * This trait contains all logic related to getting, setting, normalizing,
 * and tracking changes to custom field values on elements.
 *
 * @property array $serializedFieldValues Array of the element's serialized custom field values, indexed by their handles
 * @property array $fieldValues The element's normalized custom field values, indexed by their handles
 * @property string $fieldContext The field context this element's content uses
 * @property \CraftCms\Cms\FieldLayout\FieldLayout|null $fieldLayout The field layout used by this element
 * @property array $fieldParamNamespace The namespace used by custom field params on the request
 *
 * @internal
 */
trait HasCustomFields
{
    /**
     * @var int|null The element's field layout ID
     */
    public ?int $fieldLayoutId = null;

    /**
     * @var bool Whether the element is still awaiting its custom field values
     */
    public bool $awaitingFieldValues = false;

    private ?array $_outdatedFields = null;

    private ?array $_modifiedFields = null;

    private ?string $_fieldParamNamePrefix = null;

    private ?array $_normalizedFieldValues = null;

    /** @var array<string, mixed> */
    private array $_customFieldValues = [];

    /** @var array<string, mixed> */
    private array $_generatedFieldValues;

    /** @var array<string, true> */
    private array $_dirtyFields = [];

    /** @var int[] */
    private array $_invalidNestedElementIds = [];

    public function getFieldValues(?array $fieldHandles = null): array
    {
        return $this->collectFieldValues($fieldHandles, fn (FieldInterface $field) => $this->getFieldValue($field->handle));
    }

    public function getSerializedFieldValues(?array $fieldHandles = null): array
    {
        return $this->collectFieldValues($fieldHandles, fn (FieldInterface $field) => $field->serializeValue($this->getFieldValue($field->handle), $this));
    }

    public function getSerializedFieldValuesForDb(?array $fieldHandles = null): array
    {
        return $this->collectFieldValues($fieldHandles, fn (FieldInterface $field) => $field->serializeValueForDb($this->getFieldValue($field->handle), $this));
    }

    /**
     * @param  string[]|null  $fieldHandles
     * @param  callable(FieldInterface): mixed  $valueResolver
     * @return array<string, mixed>
     */
    private function collectFieldValues(?array $fieldHandles, callable $valueResolver): array
    {
        $values = [];

        foreach ($this->fieldLayoutFields() as $field) {
            if ($fieldHandles !== null && ! in_array($field->handle, $fieldHandles, true)) {
                continue;
            }

            $values[$field->handle] = $valueResolver($field);
        }

        return $values;
    }

    public function setFieldValues(array $values): void
    {
        foreach ($values as $fieldHandle => $value) {
            $this->setFieldValue($fieldHandle, $value);
        }
    }

    private function clonedFieldValue(string $fieldHandle): mixed
    {
        $value = $this->getFieldValue($fieldHandle);
        if (is_object($value) && ! $value instanceof UnitEnum && ! $value instanceof ContentBlock) {
            return clone $value;
        }

        return $value;
    }

    public function getFieldValue(string $fieldHandle): mixed
    {
        if ($this->hasEagerLoadedElements($fieldHandle) && ! ($this->_lazyEagerLoadedElements[$fieldHandle] ?? false)) {
            return $this->getEagerLoadedElements($fieldHandle);
        }

        $this->normalizeFieldValue($fieldHandle);

        return $this->_customFieldValues[$fieldHandle] ?? null;
    }

    public function setFieldValue(string $fieldHandle, mixed $value): void
    {
        $this->_customFieldValues[$fieldHandle] = $value;

        unset($this->_normalizedFieldValues[$fieldHandle]);

        if ($this->_initialized) {
            $this->_dirtyFields[$fieldHandle] = true;
        }

        unset($this->_eagerLoadedElements[$fieldHandle], $this->_eagerLoadedElementCounts[$fieldHandle]);
    }

    public function setFieldValueFromRequest(string $fieldHandle, mixed $value): void
    {
        $field = $this->fieldByHandle($fieldHandle);

        if (! $field) {
            throw new InvalidFieldException($fieldHandle);
        }

        $value = $field->normalizeValueFromRequest($value, $this);
        $this->setFieldValue($field->handle, $value);
        $this->_normalizedFieldValues[$field->handle] = true;
    }

    public function getOutdatedFields(): array
    {
        return array_keys($this->_outdatedFields());
    }

    public function isFieldOutdated(string $fieldHandle): bool
    {
        return isset($this->_outdatedFields()[$fieldHandle]);
    }

    public function getModifiedFields(bool $anySite = false): array
    {
        return array_keys($this->_modifiedFields($anySite));
    }

    public function isFieldModified(string $fieldHandle, bool $anySite = false): bool
    {
        return isset($this->_modifiedFields($anySite)[$fieldHandle]);
    }

    /** @return array<string, true> */
    private function _outdatedFields(): array
    {
        if (! static::trackChanges() || $this->getIsCanonical() || $this->getIsRevision()) {
            return [];
        }

        if (! isset($this->_outdatedFields)) {
            $fields = DB::table(Table::CHANGEDFIELDS)
                ->where('elementId', $this->id)
                ->where('siteId', $this->siteId)
                ->when(
                    value: $this->dateLastMerged,
                    callback: fn (Builder $query) => $query->where('dateUpdated', '>=', $this->dateLastMerged),
                    default: fn (Builder $query) => $query->where('dateUpdated', '>=', $this->dateCreated),
                )
                ->pluck('layoutElementUid')
                ->all();

            $this->_outdatedFields = $this->_layoutElementUidsToFieldHandles($fields);
        }

        return $this->_outdatedFields;
    }

    /** @return array<string, true> */
    private function _modifiedFields(bool $anySite): array
    {
        if (! static::trackChanges() || $this->getIsCanonical()) {
            return [];
        }

        $key = $anySite ? 'any' : 'this';

        if (! isset($this->_modifiedFields[$key])) {
            $fields = DB::table(Table::CHANGEDFIELDS)
                ->where('elementId', $this->id)
                ->unless($anySite, fn (Builder $query) => $query->where('siteId', $this->siteId))
                ->pluck('layoutElementUid')
                ->all();

            $this->_modifiedFields[$key] = $this->_layoutElementUidsToFieldHandles($fields);
        }

        return $this->_modifiedFields[$key];
    }

    public function isFieldDirty(string $fieldHandle): bool
    {
        if ($this->_allDirty()) {
            return true;
        }

        return isset($this->_dirtyFields[$fieldHandle]);
    }

    public function getDirtyFields(): array
    {
        if ($this->_allDirty()) {
            return array_map(fn (FieldInterface $field) => $field->handle, $this->fieldLayoutFields());
        }

        return array_keys($this->_dirtyFields);
    }

    public function setDirtyFields(array $fieldHandles, bool $merge = true): void
    {
        $newDirtyFields = array_flip($fieldHandles);

        $this->_dirtyFields = $merge && $this->_dirtyFields
            ? array_merge($this->_dirtyFields, $newDirtyFields)
            : $newDirtyFields;

        $this->_allDirty = false;
    }

    /**
     * @param  string[]  $layoutElementUids
     * @return array<string, true>
     */
    private function _layoutElementUidsToFieldHandles(array $layoutElementUids): array
    {
        if (! $layoutElementUids) {
            return [];
        }

        $uidLookup = array_flip($layoutElementUids);
        $handles = [];

        foreach ($this->getFieldLayout()->getCustomFieldElements() as $layoutElement) {
            if (isset($uidLookup[$layoutElement->uid])) {
                $handles[$layoutElement->attribute()] = true;
            }
        }

        return $handles;
    }

    public function setFieldValuesFromRequest(string $paramNamespace = ''): void
    {
        $this->setFieldParamNamespace($paramNamespace);

        $values = $this->_fieldParamNamePrefix
            ? request()->input($paramNamespace, [])
            : request()->all();

        $processedFields = [];

        do {
            $processedAnyFields = false;

            foreach ($this->fieldLayoutFields(editableOnly: true) as $field) {
                if (isset($processedFields[$field->handle])) {
                    continue;
                }

                $processedFields[$field->handle] = true;
                $processedAnyFields = true;

                if (! $this->hasFieldValueFromRequest($field, $values)) {
                    continue;
                }

                $this->setFieldValueFromRequest($field->handle, $values[$field->handle] ?? null);
            }
        } while ($processedAnyFields);
    }

    private function hasFieldValueFromRequest(FieldInterface $field, array $values): bool
    {
        if (isset($values[$field->handle])) {
            return true;
        }

        return $this->_fieldParamNamePrefix
            && UploadedFile::getInstancesByName("{$this->_fieldParamNamePrefix}.{$field->handle}");
    }

    public function getFieldParamNamespace(): ?string
    {
        return $this->_fieldParamNamePrefix;
    }

    public function setFieldParamNamespace(string $namespace): void
    {
        $this->_fieldParamNamePrefix = $namespace !== '' ? $namespace : null;
    }

    public function getGeneratedFieldValues(): array
    {
        return $this->_generatedFieldValues ?? [];
    }

    public function setGeneratedFieldValues(array $values): void
    {
        $this->_generatedFieldValues = $values;
    }

    public function getCustomFieldRawValue(string $handle): mixed
    {
        return $this->_customFieldValues[$handle] ?? null;
    }

    public function setCustomFieldRawValue(string $handle, mixed $value): void
    {
        $this->_customFieldValues[$handle] = $value;
    }

    public function hasCustomFieldValue(string $handle): bool
    {
        return array_key_exists($handle, $this->_customFieldValues);
    }

    public function getGeneratedFieldRawValue(string $handle): mixed
    {
        return ($this->_generatedFieldValues ?? [])[$handle] ?? null;
    }

    public function setGeneratedFieldRawValue(string $handle, mixed $value): void
    {
        if (! isset($this->_generatedFieldValues)) {
            $this->_generatedFieldValues = [];
        }

        $this->_generatedFieldValues[$handle] = $value;
    }

    protected function normalizeFieldValue(string $fieldHandle): void
    {
        if (isset($this->_normalizedFieldValues[$fieldHandle])) {
            return;
        }

        $field = $this->fieldByHandle($fieldHandle);

        if (! $field) {
            throw new InvalidFieldException($fieldHandle);
        }

        $this->_customFieldValues[$fieldHandle] = $field->normalizeValue($this->_customFieldValues[$fieldHandle] ?? null, $this);
        $this->_normalizedFieldValues[$fieldHandle] = true;
    }

    protected function fieldByHandle(string $handle): ?FieldInterface
    {
        if (! app(Fields::class)->isFieldHandle($handle)) {
            return null;
        }

        $field = $this->getFieldLayout()?->getFieldByHandle($handle);

        if (! $field && array_key_exists($handle, $this->_customFieldValues)) {
            $this->_customFieldValues[$handle] = null;
        }

        return $field;
    }

    /** @return FieldInterface[] */
    protected function fieldLayoutFields(bool $visibleOnly = false, bool $editableOnly = false): array
    {
        try {
            $fieldLayout = $this->getFieldLayout();
        } catch (InvalidConfigException) {
            return [];
        }

        if (! $fieldLayout) {
            return [];
        }

        if ($editableOnly) {
            return $fieldLayout->getEditableCustomFields($this);
        }

        if ($visibleOnly) {
            return $fieldLayout->getVisibleCustomFields($this);
        }

        return $fieldLayout->getCustomFields();
    }

    public function isFieldEmpty(string $handle): bool
    {
        if (
            ($fieldLayout = $this->getFieldLayout()) === null ||
            ($field = $fieldLayout->getFieldByHandle($handle)) === null
        ) {
            return true;
        }

        return $field->isValueEmpty($this->getFieldValue($handle), $this);
    }

    public function getFieldContext(): string
    {
        return app(Fields::class)->fieldContext;
    }

    public function getInvalidNestedElementIds(): array
    {
        return $this->_invalidNestedElementIds;
    }

    public function addInvalidNestedElementIds(array $ids): void
    {
        array_push($this->_invalidNestedElementIds, ...$ids);
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldLayout(): ?FieldLayout
    {
        if ($this->fieldLayoutId) {
            return app(Fields::class)->getLayoutById($this->fieldLayoutId);
        }

        return null;
    }
}
