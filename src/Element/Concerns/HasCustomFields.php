<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use Craft;
use craft\behaviors\CustomFieldBehavior;
use craft\errors\InvalidFieldException;
use craft\web\UploadedFile;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Elements\ContentBlock;
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
 * @internal
 */
trait HasCustomFields
{
    /**
     * @see _outdatedFields()
     */
    private ?array $_outdatedFields = null;

    /**
     * @see _modifiedFields()
     */
    private ?array $_modifiedFields = null;

    private ?string $_fieldParamNamePrefix = null;

    /**
     * @var array|null Record of the fields whose values have already been normalized
     */
    private ?array $_normalizedFieldValues = null;

    /**
     * @see getGeneratedFieldValues()
     * @see setGeneratedFieldValues()
     */
    private array $_generatedFieldValues;

    /**
     * @var array Record of dirty fields.
     *
     * @see getDirtyFields()
     * @see isFieldDirty()
     */
    private array $_dirtyFields = [];

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
        // Was this field's value eager-loaded?
        if ($this->hasEagerLoadedElements($fieldHandle) && ! ($this->_lazyEagerLoadedElements[$fieldHandle] ?? false)) {
            return $this->getEagerLoadedElements($fieldHandle);
        }

        // Make sure the value has been normalized
        $this->normalizeFieldValue($fieldHandle);

        return $this->getBehavior('customFields')->$fieldHandle;
    }

    public function setFieldValue(string $fieldHandle, mixed $value): void
    {
        $behavior = $this->getBehavior('customFields');
        $behavior->$fieldHandle = $value;

        // Don't assume that $value has been normalized
        unset($this->_normalizedFieldValues[$fieldHandle]);

        // If the element is fully initialized, mark the value as dirty
        if ($this->_initialized) {
            $this->_dirtyFields[$fieldHandle] = true;
        }

        // If the field value was previously eager-loaded, undo that
        unset($this->_eagerLoadedElements[$fieldHandle]);
        unset($this->_eagerLoadedElementCounts[$fieldHandle]);
    }

    public function setFieldValueFromRequest(string $fieldHandle, mixed $value): void
    {
        $field = $this->fieldByHandle($fieldHandle);

        if (! $field) {
            throw new InvalidFieldException($fieldHandle);
        }

        // Normalize it now in case the system language changes later
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

    /**
     * @return array The field handles that have been modified for this element
     */
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

            $this->_outdatedFields = $this->_layoutElementUids2fieldHandles($fields);
        }

        return $this->_outdatedFields;
    }

    /**
     * @return array The field handles that have been modified for this element
     */
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

            $this->_modifiedFields[$key] = $this->_layoutElementUids2fieldHandles($fields);
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
        $this->_dirtyFields = $merge && ! empty($this->_dirtyFields)
            ? array_merge($this->_dirtyFields, array_flip($fieldHandles))
            : array_flip($fieldHandles);

        $this->_allDirty = false;
    }

    /**
     * Returns field handles based on a list of field layout element UUIDs.
     *
     * @param  string[]  $uids
     * @return array<string, true>
     */
    private function _layoutElementUids2fieldHandles(array $uids): array
    {
        if (empty($uids)) {
            return [];
        }

        $uids = array_flip($uids);
        $handles = [];

        foreach ($this->getFieldLayout()->getCustomFieldElements() as $layoutElement) {
            if (isset($uids[$layoutElement->uid])) {
                $handles[$layoutElement->attribute()] = true;
            }
        }

        return $handles;
    }

    public function setFieldValuesFromRequest(string $paramNamespace = ''): void
    {
        $this->setFieldParamNamespace($paramNamespace);

        $values = isset($this->_fieldParamNamePrefix)
            ? Craft::$app->getRequest()->getBodyParam($paramNamespace, [])
            : Craft::$app->getRequest()->getBodyParams();

        // Run through this multiple times, in case any fields become visible as a result of other field value changes
        $processedFields = [];

        do {
            $processedAnyFields = false;

            foreach ($this->fieldLayoutFields(editableOnly: true) as $field) {
                // Have we already processed this field?
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

    /**
     * Checks if a field has a value submitted in the request.
     */
    private function hasFieldValueFromRequest(FieldInterface $field, array $values): bool
    {
        if (isset($values[$field->handle])) {
            return true;
        }

        // A file was uploaded for this field
        return isset($this->_fieldParamNamePrefix)
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

    /**
     * Normalizes a field's value.
     *
     * @param  string  $fieldHandle  The field handle
     *
     * @throws InvalidFieldException if the element doesn't have a field with the handle specified by `$fieldHandle`
     */
    protected function normalizeFieldValue(string $fieldHandle): void
    {
        // Have we already normalized this value?
        if (isset($this->_normalizedFieldValues[$fieldHandle])) {
            return;
        }

        $field = $this->fieldByHandle($fieldHandle);

        if (! $field) {
            throw new InvalidFieldException($fieldHandle);
        }

        $behavior = $this->getBehavior('customFields');
        $behavior->$fieldHandle = $field->normalizeValue($behavior->$fieldHandle, $this);
        $this->_normalizedFieldValues[$fieldHandle] = true;
    }

    /**
     * Returns the field with a given handle.
     */
    protected function fieldByHandle(string $handle): ?FieldInterface
    {
        // ignore if it's not a custom field handle
        if (! isset(CustomFieldBehavior::$fieldHandles[$handle])) {
            return null;
        }

        $field = $this->getFieldLayout()?->getFieldByHandle($handle);

        // nullify values for custom fields that are not part of this layout
        // https://github.com/craftcms/cms/issues/12539
        if (! $field) {
            $behavior = $this->getBehavior('customFields');
            if (isset($behavior->$handle)) {
                $behavior->$handle = null;
            }
        }

        return $field;
    }

    /**
     * Returns each of this element's fields.
     *
     * @param  bool  $visibleOnly  Whether to only return fields that are visible for this element
     * @param  bool  $editableOnly  Whether to only return fields that the current user can edit
     * @return FieldInterface[] This element's fields
     */
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
}
