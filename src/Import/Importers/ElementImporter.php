<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Importers;

use Closure;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Contracts\ImportableElementContainerFieldInterface;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\ImportHelper;
use CraftCms\Cms\Support\Typecast;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use League\Fractal\TransformerAbstract;
use Override;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

class ElementImporter extends BaseImporter
{
    public protected(set) ?Site $site = null;

    public protected(set) ?string $fieldLayoutUid = null;

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
        $availableElementTypes = array_map(fn ($type) => [
            'label' => $type::displayName(),
            'value' => $type,
        ], $allElementTypes);

        $defaultElementType = null;
        if (in_array(Entry::class, $allElementTypes, true)) {
            $defaultElementType = Entry::class;
        }

        return template('import/_importer-types/element-importer', [
            'readOnly' => $readOnly,
            'import' => $this,
            'availableElementTypes' => $availableElementTypes,
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

    public function fieldLayoutUid(string|int|FieldLayout|null $value): self
    {
        $fieldsService = app(Fields::class);

        if ($value instanceof FieldLayout) {
            $this->fieldLayoutUid = $value->uid;
        } elseif ($value === null) {
            $this->fieldLayoutUid = null;
        } elseif (is_numeric($value)) {
            $fieldLayout = $fieldsService->getLayoutById($value);
            if ($fieldLayout === null) {
                throw new InvalidArgumentException("No field layout found with ID: $value");
            }
            $this->fieldLayoutUid = $fieldLayout->uid;
        } elseif (is_string($value)) {
            $fieldLayout = $fieldsService->getLayoutByUid($value);
            if ($fieldLayout === null) {
                throw new InvalidArgumentException("No field layout found with UID: \"$value\".");
            }
            $this->fieldLayoutUid = $fieldLayout->uid;
        }

        return $this;
    }

    #[Override]
    public function transformer(string|null|TransformerAbstract $transformer): self
    {
        if ($transformer === null) {
            // use the default transformer for a given element;
            // e.g. Entry has an EntryTransformer that always has to be used, unless you're bringing your own transformer
            $transformer = $this->className::getDefaultTransformer();
        }

        return parent::transformer($transformer);
    }

    public function getAvailableFieldLayoutProviders(): array
    {
        $providers = [
            [
                'label' => 'Please select',
                'value' => '',
            ],
        ];

        $fieldLayouts = (new $this->className)::fieldLayouts(null);

        foreach ($fieldLayouts as $fieldLayout) {
            $providers[] = [
                'label' => $fieldLayout->provider->name,
                'value' => $fieldLayout->uid,
            ];
        }

        return $providers;
    }

    #[Override]
    public function getDestinationCols(): array
    {
        if ($this->fieldLayoutUid === null) {
            return [];
        }

        // get all the field layout elements and create an array that contains their handles;
        // if FLE is nestable (element container type), then its content is an array of its FLE handles
        // and so on
        $fieldLayout = app(Fields::class)->getLayoutByUid($this->fieldLayoutUid);

        return ImportHelper::getDestinationColsForFieldLayout($fieldLayout);
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
        $element = $this->getElement($data);

        $item = app(Import::class)->processData($this, $data, $element);

        $attributeHandles = $element->attributes();
        // $fieldHandles has custom and native field - basically all field layout elements
        $fieldHandles = array_diff(array_keys($item), $attributeHandles);
        $attributes = array_filter(array_filter($item, fn ($value, $key) => in_array($key, $attributeHandles), ARRAY_FILTER_USE_BOTH));
        $fields = array_filter($item, fn ($value, $key) => in_array($key, $fieldHandles), ARRAY_FILTER_USE_BOTH);

        $element->setAttributesFromRequest($attributes);

        $fields = $this->normalizeFields($element, $fields);

        $element->setFieldValues($fields);

        Elements::saveElement($element);
    }

    private function getElement(array $data): ElementInterface
    {
        // figure out if we're adding or editing
        $element = new $this->className;

        // if null then return a brand new ElementInterface object with just the siteId set to the selected value
        if ($this->matchCriteria === null) {
            $element->siteId = $this->site->id;

            return $element;
        }

        if (is_array($this->matchCriteria)) {
            $query = $element::find();
            $criteria = [];

            foreach ($this->matchCriteria as $key => $value) {
                if (array_key_exists((string) $value, $data)) {
                    $criteria[$key] = $data[$value];
                }
            }

            if (empty($criteria)) {
                $element->siteId = $this->site?->id;

                return $element;
            }

            Typecast::configure($query, $criteria);
            // force the selected siteId
            $query->siteId = $this->site?->id;

            return $query->one() ?? $element;
        }

        return $element;
    }

    private function normalizeFields(ElementInterface $element, array $fields): array
    {
        $fieldLayout = $element->getFieldLayout();

        if (! $fieldLayout) {
            return $fields;
        }

        foreach ($fields as $handle => $value) {
            $field = $fieldLayout->getFieldByHandle($handle);
            // if we don't have a field, or it doesn't have a normalizeValueForImport() method,
            // we don't have to worry about extra normalization, so carry on
            if (! $field) {
                continue;
            }
            if (! method_exists($field, 'normalizeValueForImport')) {
                continue;
            }

            $fields[$handle] = $field->normalizeValueForImport($value, $element);
        }

        return $fields;
    }
}
