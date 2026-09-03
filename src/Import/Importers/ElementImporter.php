<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Importers;

use Closure;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Events\ElementDeleted;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Contracts\ImportableElementContainerFieldInterface;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Import\Transformers\BaseTransformer;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Elements;
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

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

class ElementImporter extends BaseImporter
{
    public protected(set) ?Site $site = null;

    public protected(set) ?string $fieldLayout = null;

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
        $this->matchCriteria = ['id' => 'id'];

        Event::listen(function (ElementDeleted $event) {
            if ($this->trackingNestedElementDeletions) {
                $this->deletedNestedElementIds[] = $event->element->id;
            }
        });
    }

    #[Override]
    public static function displayName(): string
    {
        return t('Element Importer');
    }

    #[Override]
    protected function settingsHtml(bool $readOnly): string
    {
        $allElementTypes = Elements::getAllElementTypes();
        $availableElementTypes = collect($allElementTypes)
            ->filter(fn ($type) => $type::isImportable())
            ->map(fn ($type) => [
                'label' => $type::displayName(),
                'value' => $type,
            ]);

        $defaultElementType = null;
        if (in_array(Entry::class, $allElementTypes, true)) {
            $defaultElementType = Entry::class;
        }

        return template('import/_importer-types/element-importer', [
            'readOnly' => $readOnly,
            'import' => $this,
            'availableElementTypes' => $availableElementTypes->all(),
            'defaultElementType' => $defaultElementType,
            'availableSites' => Sites::getEditableSites()
                ->map(fn ($item) => ['label' => $item->name, 'value' => $item->handle])
                ->all(),
        ]);
    }

    //    #[Override]
    //    public function defaultName()
    //    {
    //        return t('Element Import');
    //    }

    #[Override]
    public static function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'settings.className' => fn ($attribute, $value, Closure $fail, Validator $validator) => self::validateElementType($value, $attribute, $fail, $validator),
            'settings.site' => [
                'required',
                'string',
                'max:255',
                fn ($attribute, $value, Closure $fail, Validator $validator) => self::validateSite($value, $attribute, $fail, $validator),
            ],
        ]);
    }

    /**
     * Validates that the given class name is a known importable element type.
     *
     * @param  mixed  $value  The value of the element type being validated.
     * @param  string  $attribute  The name of the attribute being validated.
     * @param  Closure  $fail  The callback function to invoke when validation fails.
     * @param  Validator  $validator  The validator instance performing the validation.
     */
    public static function validateElementType(mixed $value, string $attribute, Closure $fail, Validator $validator): bool
    {
        if (empty($value)) {
            $fail($attribute, t('Element type must be provided.'));

            return false;
        }

        $allElementTypes = Elements::getAllElementTypes();
        if (! in_array($value, $allElementTypes)) {
            $fail($attribute, t('Element type “{elementType}” is not a valid element type.', [
                'elementType' => $value,
            ]));

            return false;
        }

        return true;
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

        $allSites = Sites::getAllSites()->pluck('handle')->all();
        if (! in_array($value, $allSites)) {
            $fail($attribute, t('“{site}” is not a valid site handle.', [
                'site' => $value,
            ]));

            return false;
        }

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

    /**
     * Convenience factory returning a new instance.
     */
    public static function create(): self
    {
        return new self;
    }

    #[Override]
    public function className(string $className): self
    {
        //        $allElements = Elements::getAllElementTypes();
        //        if (! in_array($className, $allElements)) {
        //            throw new InvalidArgumentException("Class '{$className}' is not a valid element type.");
        //        }

        $this->className = $className;

        return $this;
    }

    /**
     * Resolves and sets the target site from a Site instance, id, handle, or uid (defaults to primary site if null).
     *
     * @param  string|int|Site|null  $site  The site instance, ID, handle, uid, or null.
     */
    public function site(string|int|Site|null $site): self
    {
        if ($site instanceof Site) {
            $this->site = $site;
        } elseif ($site === null) {
            $this->site = Sites::getPrimarySite();
        } elseif (is_numeric($site)) {
            $this->site = Sites::getAllSites()->firstWhere('id', $site);
            if ($this->site === null) {
                throw new InvalidArgumentException("No site found with ID: $site");
            }
        } elseif (is_string($site)) {
            $this->site = Sites::getAllSites()->firstWhere('handle', $site)
                ?? Sites::getAllSites()->firstWhere('uid', $site);
            if ($this->site === null) {
                throw new InvalidArgumentException("No site found with handle or UID: \"$site\".");
            }
        }

        return $this;
    }

    /**
     * Resolves and sets the field layout UID/type from a FieldLayout instance, id, or uid/type string.
     *
     * @param  string|int|FieldLayout|null  $value  The field layout instance, ID, uid, type, or null.
     */
    public function fieldLayout(string|int|FieldLayout|null $value): self
    {
        $fieldsService = app(Fields::class);

        if ($value instanceof FieldLayout) {
            // if the field layout is saved in the database, then it has an ID and therefore persistent UID;
            // otherwise, it's the default layout and we need to use the type
            $this->fieldLayout = $value->id ? $value->uid : $value->type;
        } elseif ($value === null) {
            $this->fieldLayout = null;
        } elseif (is_numeric($value)) {
            $fieldLayout = $fieldsService->getLayoutById($value);
            if ($fieldLayout === null) {
                throw new InvalidArgumentException("No field layout found with ID: $value");
            }
            $this->fieldLayout = $fieldLayout->uid;
        } elseif (is_string($value)) {
            $fieldLayout = $fieldsService->getLayoutByUid($value) ?? $fieldsService->getLayoutByType($value);
            if ($fieldLayout === null) {
                throw new InvalidArgumentException("No field layout found with UID or Type of: \"$value\".");
            }
            $this->fieldLayout = $fieldLayout->id ? $fieldLayout->uid : $fieldLayout->type;
        }

        return $this;
    }

    #[Override]
    public function transformer(string|null|BaseTransformer $transformer): self
    {
        if ($transformer === null) {
            // use the default transformer for a given element;
            // e.g. Entry has an EntryTransformer that always has to be used, unless you're bringing your own transformer
            $transformer = $this->className::getDefaultTransformer();
        }

        return parent::transformer($transformer);
    }

    /**
     * Returns whether the current transformer is the default one for the element type.
     */
    public function usesDefaultTransformer(): bool
    {
        $currentTransformer = $this->transformer;
        $defaultTransformer = $this->className ? $this->className::getDefaultTransformer() : null;

        // if they're simply the same - they're the same
        if ($currentTransformer === $defaultTransformer) {
            return true;
        }

        // if the current transformer is an object and the class mathes the default one - they're the same
        if ($currentTransformer instanceof BaseTransformer && $currentTransformer::class === $defaultTransformer) {
            return true;
        }

        return false;
    }

    /**
     * Builds a select-option list of field layout providers for the element class (singular or multiple layouts).
     * The list of field layout providers as label/value pairs.
     */
    public function getAvailableFieldLayoutProviders(): array
    {
        $element = (new $this->className);

        // first try to get all field layouts
        $fieldLayouts = $element::fieldLayouts(null);

        // if we got zero results - try with a singular method
        if (count($fieldLayouts) === 0) {
            $fieldLayout = $element->getFieldLayout();

            // if we were able to get the field layout this way, then there can only be one for the element;
            // like there's only one for Address or User element
            if ($fieldLayout) {
                return [
                    [
                        'label' => $element::displayName(),
                        'value' => $fieldLayout->id ? $fieldLayout->uid : $fieldLayout->type,
                    ],
                ];
            }
        }

        $providers = [
            [
                'label' => 'Please select',
                'value' => '',
            ],
        ];
        foreach ($fieldLayouts as $fieldLayout) {
            $providers[] = [
                'label' => $fieldLayout->provider?->name ?? $fieldLayout->type,
                'value' => $fieldLayout->uid,
            ];
        }

        return $providers;
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
                'isProperty' => true,
            ], $props);
        }

        if ($this->fieldLayout === null) {
            return $propertyCols;
        }

        $fieldsService = app(Fields::class);
        $fieldLayout = $fieldsService->getLayoutByUid($this->fieldLayout) ?? $fieldsService->getLayoutByType($this->fieldLayout);

        $fieldLayoutCols = ImportHelper::getDestinationColsForFieldLayout($fieldLayout);

        return array_merge($propertyCols, $fieldLayoutCols);
    }

    #[Override]
    public function getSourceDataCols(): array
    {
        $filePath = BaseImporter::resolvedFilePath($this->file);

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
            $element->setAttributesForImport($attributes);
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

        $hasChanges = $skipChangeDetection
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
     * Enables keeping missing nested elements for any container field opted in via
     * `keepMissingNestedElements`, returning the fields that should be restored after the save.
     *
     * @return ImportableElementContainerFieldInterface[]
     */
    private function enableKeepMissingNestedElements(ElementInterface $element): array
    {
        return $this->collectAndEnableKeepFields($element->getFieldLayout(), []);
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
     * @param  array<int, string>  $path  The path segments (handle/provider handle/`fields`) leading to $fieldLayout.
     * @return ImportableElementContainerFieldInterface[]
     */
    private function collectAndEnableKeepFields(?FieldLayout $fieldLayout, array $path): array
    {
        if (! $fieldLayout) {
            return [];
        }

        $restoreKeepFlagFields = [];

        foreach ($fieldLayout->getCustomFields() as $field) {
            if (! $field instanceof ImportableElementContainerFieldInterface) {
                continue;
            }

            $fieldPath = [...$path, $field->handle];

            if ($field->canKeepMissingNestedElements()) {
                // loose cast: a real checkbox submission survives as int 1/0 (or even the
                // string "1"/"0") after json_decode(), not strictly bool true/false
                $shouldKeep = (bool) Arr::get($this->keepMissingNestedElements, implode('.', [...$fieldPath, '__keep__']));

                if ($shouldKeep) {
                    $field->setKeepMissingNestedElements(true);
                    $restoreKeepFlagFields[] = $field;
                }
            }

            foreach ($field->getFieldLayoutProviders() as $provider) {
                $restoreKeepFlagFields = [
                    ...$restoreKeepFlagFields,
                    ...$this->collectAndEnableKeepFields($provider->getFieldLayout(), [...$fieldPath, $provider->getHandle(), 'fields']),
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
    private function getRootElement(array $data): ElementInterface
    {
        // figure out if we're adding or editing
        $element = new $this->className;

        $element = $element->prepareNewElementForImport($this, $data);

        // if we don't have matchCriteria, then return a new Element
        if (empty($data['matchCriteria'])) {
            return $element;
        }

        if (is_array($data['matchCriteria'])) {
            $query = $element::find()
                ->drafts(null)
                ->status(null);

            // give element a chance to adjust the query
            $element->prepareRootElementImportQuery($query);

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
