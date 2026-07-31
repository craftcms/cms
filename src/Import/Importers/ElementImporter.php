<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Importers;

use Closure;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Contracts\ImportableElementContainerFieldInterface;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Transformers\BaseTransformer;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Importer;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\ImportHelper;
use CraftCms\Cms\Support\Typecast;
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
     * Calls the parent constructor then sets default match criteria to `['id' => 'id']`.
     *
     * @param array|null $config Optional config array, potentially containing a `uid` key.
     */
    public function __construct(?array $config = null)
    {
        parent::__construct($config);
        $this->matchCriteria = ['id' => 'id'];
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
     * @param mixed $value The value of the element type being validated.
     * @param string $attribute The name of the attribute being validated.
     * @param Closure $fail The callback function to invoke when validation fails.
     * @param Validator $validator The validator instance performing the validation.
     * @return bool
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
     * @param mixed $value The value of the site handle being validated.
     * @param string $attribute The name of the attribute being validated.
     * @param Closure $fail The callback function to invoke when validation fails.
     * @param Validator $validator The validator instance performing the validation.
     * @return bool
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
     *
     * @return self
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
     * @param string|int|Site|null $site The site instance, ID, handle, uid, or null.
     * @return self
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
     * @param string|int|FieldLayout|null $value The field layout instance, ID, uid, type, or null.
     * @return self
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
     *
     * @return bool
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
     *
     * @return array
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

        return app(Import::class)->getDataHeadings($filePath);
    }

    #[Override]
    public function importItem(array $data): void
    {
        // figure out if we're adding or updating
        $element = $this->getRootElement($data);

        // if ($element->id !== null) {
        // todo (iwona): not sure if we want to be this extreme;
        // when used via command or any other option where user is not logged in to the CP
        // it'll force the entry data to have author(s) specified or for them to be turned off on section's level
        // or maybe we should set the live scenario by default but if someone maps the status,
        // and it's set to disabled then we go with the default or essential scenario?
        // or, we add a setting to the config that allows you to choose if you want the data validated on import and its on by default?
        // -----
        // and validation scenario is "live"
        $element->ruleset->useScenario(ElementRules::SCENARIO_LIVE);
        // }

        $item = app(Import::class)->processData($this, $data, $element);

        // normalization and validation of attributes happens in the transformer and in the setAttributesForImport() method
        $attributeHandles = $element->attributes();
        // $fieldHandles has custom and native fields - basically all field layout elements
        $fieldHandles = array_diff(array_keys($item), $attributeHandles);
        $attributes = array_filter($item, fn ($key) => in_array($key, $attributeHandles), ARRAY_FILTER_USE_KEY);
        $fields = array_filter($item, fn ($key) => in_array($key, $fieldHandles), ARRAY_FILTER_USE_KEY);

        $element->setAttributesForImport($attributes);

        // todo (iwona): make the match criteria work for nested elements too!
        $fields = $this->normalizeFields($element, $fields);

        $element->setFieldValues($fields);

        // now get attributes that are containers - those need special processing
        $containerAttributes = ImportHelper::getImportableContainerProperties($this);
        foreach ($containerAttributes as $attribute) {
            if (isset($item[$attribute['name']]) && method_exists($element, 'importIntoContainerAttribute')) {
                $element->importIntoContainerAttribute($attribute, $item, $this);
            }
        }

        if (! Elements::saveElement($element)) {
            Importer::warning(
                'Unable to save element being imported (elementId: '.($element->id ?? 'new').'): '.
                print_r($element->errors()->all(), true),
                ['data' => $item]
            );
        }
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

            if (empty($criteria)) {
                $element->siteId = $this->site?->id;

                return $element;
            }

            Typecast::configure($query, $criteria);

            // ensure we use the config's siteId, not one from the matchCriteria
            // that's why we haven't set it earlier on
            $query->siteId = $this->site?->id;

            return $query->one() ?? $element;
        }

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
