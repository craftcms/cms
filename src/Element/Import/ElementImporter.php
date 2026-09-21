<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Import;

use Closure;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Events\ElementDeleted;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Contracts\ImportableElementContainerFieldInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field as FormField;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Import;
use CraftCms\Cms\Support\Facades\ImportLog;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\ImportHelper;
use CraftCms\Cms\Support\Typecast;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use Override;
use Throwable;

use function CraftCms\Cms\t;

abstract class ElementImporter extends BaseImporter
{
    public protected(set) ?Site $site = null;

    public protected(set) ?string $fieldLayout = null;

    /**
     * @var array|null
     *
     * array => a tree keyed by field handle, mirroring $matchCriteria's shape (including a
     *  `fields` segment and provider/entry-type handle for each level of nesting). Since a
     *  container field (Matrix, Addresses) is the only kind of node in this tree that both
     *  has its own decision *and* may contain further nested container fields, each
     *  container field's own decision lives under a reserved `__keep__` leaf sitting
     *  alongside its children, e.g. `['outerMatrix' => ['__keep__' => true, 'someEntryType' =>
     *  ['fields' => ['innerMatrix' => ['__keep__' => false]]]]]`; a `true` leaf keeps that
     *  field's nested items missing from the incoming data instead of pruning them; a field
     *  with no `__keep__` entry is pruned (the default), matching how Craft already saves
     *  that field type outside of import;
     *
     *  null => nothing is kept; missing nested items are pruned for every field that supports it
     */
    public protected(set) ?array $keepMissingNestedElements = null;

    /**
     * Whether an `Elements::saveElement()` call is currently in progress, so the `ElementDeleted`
     * listener registered in the constructor knows to collect deletions caused by it.
     */
    private bool $trackingNestedElementDeletions = false;

    /**
     * Element IDs deleted (via nested-item pruning) during the current `Elements::saveElement()` call.
     */
    private array $deletedNestedElementIds = [];

    /**
     * Calls the parent constructor then sets default match criteria to `['id' => 'id']`.
     *
     * @param  array|null  $config  Optional config array, potentially containing a `uid` key.
     */
    public function __construct(?array $config = null)
    {
        parent::__construct($config);
        // $this->matchCriteria = ['id' => 'id'];

        Event::listen(function (ElementDeleted $event) {
            if ($this->trackingNestedElementDeletions) {
                $this->deletedNestedElementIds[] = $event->element->id;
            }
        });
    }

    #[Override]
    public static function getDefaultTransformer(): ?string
    {
        return ElementTransformer::class;
    }

    #[Override]
    public function settingsForm(FormContext $context): array
    {
        $availableSites = Sites::getEditableSites()
            ->map(fn ($item) => ['label' => $item->name, 'value' => $item->handle])
            ->all();

        return [
            'context' => $context,
            'nodes' => [
                FormField::make(t('Site'), Choice::make('site')
                    ->value($this->site?->handle ?? Sites::getPrimarySite()->handle)
                    ->options($availableSites))
                    ->instructions(t('The site you want to import the data into'))
                    ->required(),
            ],
        ];
    }

    #[Override]
    public function refreshSettingsForm(array $settings): void
    {
        if (array_key_exists('site', $settings)) {
            $this->site($settings['site']);
        }
        if (array_key_exists('fieldLayout', $settings)) {
            $this->fieldLayout($settings['fieldLayout']);
        }
    }

    #[Override]
    public function storeSettings(array $settings): void
    {
        $this->site($settings['site']);
        // $this->fieldLayout($settings['fieldLayout']);
    }

    #[Override]
    public function getSettings(): array
    {
        $settings['keepMissingNestedElements'] = $this->keepMissingNestedElements;
        $settings['site'] = $this->site->uid;
        $settings['fieldLayout'] = $this->fieldLayout;

        return $settings;
    }

    /**
     * Resolves and sets the target site from a Site instance, id, handle, or uid.
     * Defaults to the primary site if null.
     *
     * @param  string|int|Site|null  $site  The site instance, ID, handle, uid, or null.
     */
    public function site(string|int|Site|null $site): self
    {
        $resolved = static::normalizeSite($site);

        if ($resolved === null) {
            throw new InvalidArgumentException(is_numeric($site)
                ? "No site found with ID: $site"
                : "No site found with handle or UID: \"$site\".");
        }

        $this->site = $resolved;

        return $this;
    }

    /**
     * Resolves and sets the field layout UID/type from a FieldLayout instance, id, or uid/type string.
     *
     * @param  string|int|FieldLayout|null  $value  The field layout instance, ID, uid, type, or null.
     */
    public function fieldLayout(string|int|FieldLayout|null $value): self
    {
        if ($value === null) {
            $this->fieldLayout = null;

            return $this;
        }

        $fieldLayout = static::normalizeFieldLayout($value, create: true);

        if ($fieldLayout === null) {
            throw new InvalidArgumentException(is_numeric($value)
                ? "No field layout found with ID: $value"
                : "No field layout found with UID or Type of: \"$value\".");
        }

        // if the field layout is saved in the database, then it has an ID and therefore persistent UID;
        // otherwise, it's the default layout and we need to use the type
        $this->fieldLayout = $fieldLayout->id ? $fieldLayout->uid : $fieldLayout->type;

        return $this;
    }

    /**
     * Gives an importer whose field layout isn't chosen by the user a chance to resolve one.
     *
     * Called once the importer has been built from a step's settings. Most element types
     * resolve their layout from a setting (an entry type, a volume), so the default does
     * nothing; override it where the layout is fixed for the element type instead.
     */
    public function resolveDefaultFieldLayout(): void {}

    /**
     * Sets the container field handles that should keep nested elements missing from the
     * incoming data instead of pruning them, and returns the current instance.
     *
     * @param  array|null  $keepMissingNestedElements  The field handles to keep, either as the nested
     *                                                 `__keep__`-leaf tree (matching $matchCriteria's
     *                                                 shape) or a flat list of dot-notation handles to keep.
     */
    public function keepMissingNestedElements(?array $keepMissingNestedElements = null): self
    {
        if ($keepMissingNestedElements !== null) {
            $keepMissingNestedElements = ImportHelper::decodeRecursive($keepMissingNestedElements);

            if (array_is_list($keepMissingNestedElements)) {
                $keepMissingNestedElements = Arr::undot(array_fill_keys(
                    array_map(fn ($handle) => $handle.'.__keep__', $keepMissingNestedElements),
                    true
                ));
            }

            $this->keepMissingNestedElements = $keepMissingNestedElements;
        }

        return $this;
    }

    #[Override]
    public static function getSettingsRules(): array
    {
        return array_merge(parent::getSettingsRules(), [
            'settings.fieldLayout' => fn ($attribute, $value, Closure $fail, Validator $validator) => static::validateFieldLayout($value, $attribute, $fail, $validator),
            'settings.site' => [
                'required',
                'string',
                'max:255',
                fn ($attribute, $value, Closure $fail, Validator $validator) => static::validateSite($value, $attribute, $fail, $validator),
            ],
            'settings.keepMissingNestedElements' => ['array'],
        ]);
    }

    #[Override]
    protected function toValidationData(): array
    {
        $data = parent::toValidationData();
        $data['settings']['site'] = $this->site?->handle;
        $data['settings']['fieldLayout'] = $this->fieldLayout ?? null;

        return $data;
    }

    /**
     * Validates that the given handle matches a known site.
     *
     * @param  mixed  $value  The value of the site handle being validated.
     * @param  string  $attribute  The name of the attribute being validated.
     * @param  Closure  $fail  The callback function to invoke when validation fails.
     * @param  Validator  $validator  The validator instance performing the validation.
     */
    public static function validateSite(mixed $value, string $attribute, Closure $fail, Validator $validator): bool
    {
        if (empty($value)) {
            $fail($attribute, t('Site must be provided.'));

            return false;
        }

        if (static::normalizeSite($value) === null) {
            $fail($attribute, t('“{site}” is not a valid site handle.', [
                'site' => $value,
            ]));

            return false;
        }

        return true;
    }

    /**
     * Validates that the given field layout is a valid FieldLayout instance, numeric ID, UID, or element-type string.
     */
    public static function validateFieldLayout(mixed $value, string $attribute, Closure $fail, Validator $validator): bool
    {
        // if we don't have a UID, then the config is coming from the CLI or file-based and won't have a fieldLayout
        if (! isset($validator->getData()['uid'])) {
            return true;
        }

        // can't be empty
        if (empty($value)) {
            $fail($attribute, t('Field layout must be provided.'));

            return false;
        }

        // has to exist (never create a layout as a side effect of validation)
        $fieldLayout = static::normalizeFieldLayout($value);
        if ($fieldLayout === null) {
            $fail($attribute, t('No field layout found for “{fieldLayout}”.', [
                'fieldLayout' => $value,
            ]));

            return false;
        }

        //        // has to belong to the element type if we know it
        //        $className = Arr::get($validator->getData(), 'settings.className');
        //        if (is_string($className) && $className !== '' && $fieldLayout->type !== $className) {
        //            $fail($attribute, t('Field layout does not belong to element type “{elementType}”.', [
        //                'elementType' => $className,
        //            ]));
        //
        //            return false;
        //        }

        return true;
    }

    #[Override]
    public static function validateMap(mixed $value, string $attribute, Closure $fail, Validator $validator, array $params = []): bool
    {
        // in case of an element importer, the $params might contain the field this map partial is for
        // if $params is empty, then we're validating the whole map
        if (! empty($params['field'])) {
            $field = $params['field'];

            if ($field instanceof ImportableElementContainerFieldInterface) {
                $field->validateMapping($value, $attribute, $fail, $validator, $params);
            }
        }

        return true;
    }

    #[Override]
    public function getDestinationCols(): array
    {
        $propertyCols = [];

        // attribute mapping - get all properties marked as importable
        $props = ImportHelper::getImportableProperties($this);

        // exclude all those that are not supposed to be available for UI mapping
        $props = array_filter($props, fn ($prop) => ! isset($prop['excludeFromUiMapping']) || $prop['excludeFromUiMapping'] === false);

        if (! empty($props)) {
            $propertyCols = array_map(fn ($prop) => [
                'handle' => $prop['name'],
                'label' => $prop['label'],
                'prefixedHandleForMap' => Html::namespaceInputName($prop['name'], 'map'),
                'prefixedHandleForMatchCriteria' => Html::namespaceInputName($prop['name'], 'matchCriteria'),
                'prefixedHandleForClear' => Html::namespaceInputName($prop['name'], 'clearableItems'),
                'prefixedHandle' => $prop['name'],
                'prefixedHandleAsArray' => Arr::bracketsToArray($prop['name']),
                'isContainer' => $prop['isContainer'] ?? false,
                'canBeMatchCriteria' => $prop['canBeMatchCriteria'] ?? true,
                'canBeCleared' => $prop['canBeCleared'] ?? true,
                'canBeSet' => $prop['canBeSet'] ?? true,
                'isProperty' => true,
            ], $props);
        }

        if ($this->fieldLayout === null) {
            return $propertyCols;
        }

        $fieldLayout = self::normalizeFieldLayout($this->fieldLayout);

        $fieldLayoutCols = ImportHelper::getDestinationColsForFieldLayout($fieldLayout);

        return array_merge($propertyCols, $fieldLayoutCols);
    }

    #[Override]
    public function getSourceDataCols(): array
    {
        $filePath = BaseImporter::resolvedFilePath($this->file);

        // a config can be saved before its file is chosen, and the map screen still renders
        if ($filePath === null) {
            return [];
        }

        return Import::getDataHeadings($filePath);
    }

    #[Override]
    public function importItem(array $data): void
    {
        // figure out if we're adding or updating
        $element = $this->getRootElement($data);
        $element->markAsImporting();

        $item = Import::processData($this, $data, $element);

        $isNew = $element->id === null;

        // normalization and validation of attributes happens in the transformer and in the setAttributesForImport() method
        $attributeHandles = $element->attributes();
        // $fieldHandles has custom and native fields - basically all field layout elements
        $fieldHandles = array_diff(array_keys($item), $attributeHandles);

        // get a list of container properties
        $containerProps = ImportHelper::getImportableContainerProperties($this);
        // and deduce attributes from those
        $containerAttributes = collect($containerProps)->map(fn ($prop) => $prop['name'])->all();

        // exclude container attributes from field handles
        $fieldHandles = empty($containerAttributes) ? $fieldHandles : array_diff($fieldHandles, $containerAttributes);

        $attributes = array_filter($item, fn ($key) => in_array($key, $attributeHandles), ARRAY_FILTER_USE_KEY);
        $fields = array_filter($item, fn ($key) => in_array($key, $fieldHandles), ARRAY_FILTER_USE_KEY);

        // If any container property has data present in $item, treat the element as changed
        // (there's no cheap/reliable way to diff this,
        // plus the container handling may depend on the parent being re-saved, like it does for User addresses)
        $hasContainerData = collect($containerProps)->contains(fn ($prop) => isset($item[$prop['name']]));

        // no need to snapshot old values when the element will be saved unconditionally anyway
        $skipChangeDetection = $isNew || $hasContainerData;
        $oldAttributeValues = $skipChangeDetection ? [] : $this->snapshotAttributeValues($element, array_keys($attributes));
        $oldFieldValues = $skipChangeDetection ? [] : $this->snapshotFieldValues($element, array_keys($fields));

        if (! empty($attributes)) {
            $this->setAttributesForImport($element, $attributes);
        }

        if (! empty($fields)) {
            $fields = $this->normalizeFields($element, $fields);
            $element->setFieldValues($fields);
        }

        // attributes that are containers need special processing
        foreach ($containerProps as $prop) {
            if (isset($item[$prop['name']]) && method_exists($element, 'importIntoContainerAttribute')) {
                $element->importIntoContainerAttribute($prop, $item, $this);
            }
        }

        // A container field whose nested element doesn't exist yet can serialize identically before
        // and after the incoming value is set (ContentBlock::serializeValue() returns null while the
        // block has no id), so the diff below can't see the new content. Treat incoming data for a
        // container field that's currently empty as a change.
        $hasNewContainerFieldData = ! $skipChangeDetection
            && $this->hasNewContainerFieldData($element, $fields, $oldFieldValues);

        $hasChanges = $skipChangeDetection
            || $hasNewContainerFieldData
            || $this->attributeValuesChanged($element, $oldAttributeValues)
            || $this->fieldValuesChanged($element, $oldFieldValues);

        if (! $hasChanges) {
            return;
        }

        if ($element->enabled && $element->getEnabledForSite()) {
            $element->ruleset->useScenario(ElementRules::SCENARIO_LIVE);
        } else {
            $element->ruleset->useScenario(ElementRules::SCENARIO_ESSENTIALS);
        }

        $restoreKeepFlagFields = $this->enableKeepMissingNestedElements($element);

        $this->trackingNestedElementDeletions = true;
        $this->deletedNestedElementIds = [];

        try {
            if (! Elements::saveElement($element)) {
                ImportLog::warning(
                    'Unable to save element being imported (elementId: '.($element->id ?? 'new').'): '.
                    print_r($element->errors()->all(), true),
                    ['data' => $item]
                );
            }
        } finally {
            $this->trackingNestedElementDeletions = false;

            foreach ($restoreKeepFlagFields as $field) {
                $field->setKeepMissingNestedElements(false);
            }
        }

        if (! empty($this->deletedNestedElementIds)) {
            ImportLog::info(
                'Pruned nested elements missing from imported data (elementId: '.($element->id ?? 'new').')',
                ['elementId' => $element->id, 'prunedElementIds' => $this->deletedNestedElementIds]
            );
        }
    }

    /**
     * Resolves a Site instance, numeric ID, handle, or UID to a Site, or null if not found.
     * A null value resolves to the primary site.
     */
    private static function normalizeSite(string|int|Site|null $value): ?Site
    {
        return match (true) {
            $value instanceof Site => $value,
            $value === null => Sites::getPrimarySite(),
            is_numeric($value) => Sites::getSiteById((int) $value),
            default => static::siteByUidOrNull($value) ?? Sites::getSiteByHandle($value),
        };
    }

    /**
     * Resolves a FieldLayout instance, numeric ID, UID, or element-type string to a FieldLayout, or null if not found.
     * A null value resolves to null.
     */
    private static function normalizeFieldLayout(string|int|FieldLayout|null $value, bool $create = false): ?FieldLayout
    {
        return match (true) {
            $value instanceof FieldLayout => $value,
            $value === null => null,
            is_numeric($value) => Fields::getLayoutById((int) $value),
            default => Fields::getLayoutByUid($value) ?? Fields::getLayoutByType($value, create: $create),
        };
    }

    /**
     * Prepares a new element instance for import.
     */
    public function prepareNewRootElementForImport(array &$data, ?ElementInterface $element = null): ElementInterface
    {
        $element ??= new ($this::targetClass());

        // ensure site is set
        $element->siteId = $this->site->id;

        return $element;
    }

    /**
     * Prepare the element query that searches for the root element we're importing into
     */
    public function prepareRootElementImportQuery(ElementInterface $element, ElementQueryInterface $query): ElementQueryInterface
    {
        // by default, we don't need to adjust the element query
        return $query;
    }

    /**
     * Sets element's importable attributes.
     */
    public function setAttributesForImport(ElementInterface $element, array $attributes): void
    {
        // the ID and UID can only be used to match on, we cannot have them be set via the import
        unset($attributes['id'], $attributes['uid']);

        // by default, simply set the attributes
        $element->setAttributesFromRequest($attributes);
    }

    #[Override]
    public static function isElementImporter(): bool
    {
        return true;
    }

    /**
     * `Sites::getSiteByUid()` throws an error when the UID isn't found,
     * so we need this method to catch it and return null in that case.
     */
    private static function siteByUidOrNull(string $uid): ?Site
    {
        try {
            return Sites::getSiteByUid($uid);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Enables keeping missing nested elements for any container field opted in via
     * `keepMissingNestedElements`, returning the fields that should be restored after the save.
     *
     * @return ImportableElementContainerFieldInterface[]
     */
    private function enableKeepMissingNestedElements(ElementInterface $element): array
    {
        return $this->collectAndEnableKeepFields($element->getFieldLayout(), null, null, null);
    }

    /**
     * Recursively walks a field layout's container fields (and, for each one, the field layouts of
     * every provider it offers, e.g. a Matrix field's entry types) looking for fields opted in to
     * keeping missing nested elements via `keepMissingNestedElements`, enabling each one it finds.
     *
     * Fields are resolved strictly through the given `FieldLayout` object (never via a direct
     * lookup like `Fields::getFieldById()`), so the instance mutated here is the exact same one
     * the real, recursive `Elements::saveElement()` cascade will encounter later.
     *
     * @param  FieldInterface|null  $ownerField  The container field $fieldLayout belongs to, if any.
     * @param  mixed  $provider  The field layout provider $fieldLayout came from, if any.
     * @param  string|null  $prefix  $ownerField's own prefixed handle, as the mapping screen names it.
     * @return ImportableElementContainerFieldInterface[]
     */
    private function collectAndEnableKeepFields(
        ?FieldLayout $fieldLayout,
        ?FieldInterface $ownerField,
        mixed $provider,
        ?string $prefix,
    ): array {
        if (! $fieldLayout) {
            return [];
        }

        $restoreKeepFlagFields = [];

        foreach ($fieldLayout->getCustomFields() as $field) {
            if (! $field instanceof ImportableElementContainerFieldInterface) {
                continue;
            }

            // The keep tree is keyed by the names the mapping screen generates, so ask the same
            // helper the UI does rather than assuming a [handle][providerHandle][fields] shape -
            // a content block is its own layout provider, which that assumption would spell twice.
            [, , , $prefixedHandle, $prefixedHandleAsArray] = ImportHelper::getPrefixedHandlesForMapping(
                $field->handle,
                $ownerField,
                $field,
                $fieldLayout,
                $provider,
                $prefix,
            );

            if ($field->canKeepMissingNestedElements()) {
                // loose cast: a real checkbox submission survives as int 1/0 (or even the
                // string "1"/"0") after json_decode(), not strictly bool true/false
                $shouldKeep = (bool) Arr::get($this->keepMissingNestedElements, implode('.', [...$prefixedHandleAsArray, '__keep__']));

                if ($shouldKeep) {
                    $field->setKeepMissingNestedElements(true);
                    $restoreKeepFlagFields[] = $field;
                }
            }

            foreach ($field->getFieldLayoutProviders() as $nestedProvider) {
                $restoreKeepFlagFields = [
                    ...$restoreKeepFlagFields,
                    ...$this->collectAndEnableKeepFields($nestedProvider->getFieldLayout(), $field, $nestedProvider, $prefixedHandle),
                ];
            }
        }

        return $restoreKeepFlagFields;
    }

    /**
     * Snapshots the element's current values for the given attribute handles.
     */
    private function snapshotAttributeValues(ElementInterface $element, array $handles): array
    {
        $values = [];

        foreach ($handles as $handle) {
            $values[$handle] = $element->$handle;
        }

        return $values;
    }

    /**
     * Snapshots the element's current serialized values for the given field handles.
     */
    private function snapshotFieldValues(ElementInterface $element, array $handles): array
    {
        $fieldLayout = $element->getFieldLayout();
        $values = [];

        foreach ($handles as $handle) {
            $field = $fieldLayout?->getFieldByHandle($handle);
            $values[$handle] = $field
                ? $field->serializeValue($element->getFieldValue($handle), $element)
                : $element->getFieldValue($handle);
        }

        return $values;
    }

    /**
     * Compares the given old attribute values against the element's current values.
     */
    private function attributeValuesChanged(ElementInterface $element, array $oldValues): bool
    {
        return array_any($oldValues, fn ($oldValue, $handle) => $oldValue != $element->$handle);
    }

    /**
     * Compares the given old serialized field values against the element's current values.
     */
    /**
     * Returns whether the incoming data fills a container field that the element has no nested
     * elements for yet - a case comparing serialized values can't detect.
     *
     * @param  array<string, mixed>  $fields
     * @param  array<string, mixed>  $oldValues
     */
    private function hasNewContainerFieldData(ElementInterface $element, array $fields, array $oldValues): bool
    {
        $fieldLayout = $element->getFieldLayout();

        if (! $fieldLayout) {
            return false;
        }

        foreach ($fields as $handle => $value) {
            if (empty($value) || ! empty($oldValues[$handle])) {
                continue;
            }

            if ($fieldLayout->getFieldByHandle((string) $handle) instanceof ImportableElementContainerFieldInterface) {
                return true;
            }
        }

        return false;
    }

    private function fieldValuesChanged(ElementInterface $element, array $oldValues): bool
    {
        $fieldLayout = $element->getFieldLayout();

        foreach ($oldValues as $handle => $oldValue) {
            $field = $fieldLayout?->getFieldByHandle($handle);
            $newValue = $field
                ? $field->serializeValue($element->getFieldValue($handle), $element)
                : $element->getFieldValue($handle);

            // using !== cause the order of the keys is important here
            if ($oldValue !== $newValue) {
                return true;
            }
        }

        return false;
    }

    /**
     * Prepares a new element or looks up an existing one via a match criteria query, applying site ID.
     */
    private function getRootElement(array &$data): ElementInterface
    {
        // figure out if we're adding or editing
        $element = $this->prepareNewRootElementForImport($data);

        // if we don't have matchCriteria, then return a new Element
        if (empty($data['matchCriteria'])) {
            return $element;
        }

        if (is_array($data['matchCriteria'])) {
            $query = $element::find()
                ->drafts(null)
                ->status(null);

            // give element a chance to adjust the query
            $this->prepareRootElementImportQuery($element, $query);

            // by now the match criteria from various sources (ui, config, transformer) should have been merged,
            // and the values from incoming data should have been applied to it
            $criteria = $data['matchCriteria'];

            // if we still don't have criteria, return a new Element
            if (empty($criteria)) {
                $element->siteId = $this->site?->id;

                return $element;
            }

            Typecast::configure($query, $criteria);

            // ensure we use the config's siteId, not one from the matchCriteria
            // that's why we haven't set it earlier on
            $query->siteId = $this->site?->id;

            // return found or new element
            return $query->one() ?? $element;
        }

        // return new element
        return $element;
    }

    /**
     * Runs each field's `normalizeValueForImport()` (if defined) over the incoming field data.
     */
    private function normalizeFields(ElementInterface $rootElement, array $data): array
    {
        $fieldLayout = $rootElement->getFieldLayout();

        if (! $fieldLayout) {
            return $data;
        }

        foreach ($data as $handle => $value) {
            $field = $fieldLayout->getFieldByHandle($handle);
            // if we don't have a field, or it doesn't have a normalizeValueForImport() method,
            // we don't have to worry about extra normalization, so carry on
            if (! $field) {
                continue;
            }
            if (! method_exists($field, 'normalizeValueForImport')) {
                continue;
            }

            $data[$handle] = $field->normalizeValueForImport($value, $this, $rootElement);
        }

        return $data;
    }
}
