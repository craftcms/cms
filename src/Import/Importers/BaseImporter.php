<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Importers;

use Closure;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Import\Transformers\BaseTransformer;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Import;
use CraftCms\Cms\Support\Facades\ImportConfig;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Validation\Rules\HandleRule;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

abstract class BaseImporter
{
    public protected(set) ?string $name = null;

    public protected(set) ?string $handle = null;

    public protected(set) ?string $description = null;

    public protected(set) ?string $className = null;

    public protected(set) ?string $file = null;

    public protected(set) string|BaseTransformer|null $transformer = null;

    public protected(set) array $map = [];

    /**
     * @var Closure|array|null
     *
     * array => key is what to import into (e.g. a field handle or attribute name)
     *  value is the name/key/property from the incoming data;
     *  by default, [id => id] is used, meaning elements are matched on their ID, and we expect an 'id' key in the provided data;
     *  example: ['plainText' => 'myPlainText'] means that you have a plainText field in you field layout or column in your table
     *    and you want to import the value of 'myPlainText' column/property from the incoming csv/json/xml;
     *  the array can be multidimensional;
     *  the value should be the name/key/property from the incoming data - it should not be adjusted for mapping at this stage;
     *
     *  null => don't match against existing elements; import all incoming data as new
     */
    public protected(set) ?array $matchCriteria = null;

    /**
     * @var array|null
     *
     * array => key is the field/attribute handle to clear when the incoming data doesn't provide a value for it
     *  (or provides an empty one); value is truthy (1/true) to mark it as clearable;
     *  the array can be multidimensional to reach into container fields, mirroring $matchCriteria's shape;
     *  for convenience, file-based configs may instead provide a flat list of dot-notation handles
     *  (e.g. ['heading', 'body']), which gets normalized into the nested truthy-leaf shape;
     *
     *  null => nothing is cleared; missing/empty incoming values are left untouched
     */
    public protected(set) ?array $clearableItems = null;

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

    public ?string $uid = null;

    /**
     * Sets `$this->uid` from a config array if provided.
     *
     * @param  array|null  $config  Optional config array, potentially containing a `uid` key.
     */
    public function __construct(?array $config = null)
    {
        if (! empty($config)) {
            $this->uid = $config['uid'] ?? null;
        }
    }

    /**
     * Returns the display name for the importer.
     */
    public static function displayName(): string
    {
        return t('Base Importer');
    }

    /**
     * Determines if the importer is editable.
     * If the importer has a UID, it means it's stored in the database, and therefore it's editable via the Control Panel.
     * Otherwise, it's a custom importer that comes e.g. from a file.'
     */
    public function isEditable(): bool
    {
        return isset($this->uid);
    }

    /**
     * Generates the HTML for the settings view.
     */
    public function getSettingsHtml(): string
    {
        return $this->settingsHtml(false);
    }

    /**
     * Generates the HTML for read-only settings.
     */
    public function getReadOnlySettingsHtml(): string
    {
        return $this->settingsHtml(true);
    }

    /**
     * Generates the settings HTML for the importer.
     *
     * @param  bool  $readOnly  Indicates whether the settings should be rendered in a read-only state.
     */
    protected function settingsHtml(bool $readOnly): string
    {
        return template('import/_importer-types/base-importer', [
            'readOnly' => $readOnly,
            'import' => $this,
        ]);
    }

    /**
     * Determines if the current importer is for an Element.
     */
    public function isElementImport(): bool
    {
        return is_subclass_of($this->className, ElementInterface::class);
    }

    /**
     * Defines the validation rules for the importer.
     */
    public static function getRules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'handle' => [
                'required',
                'string',
                'max:255',
                new HandleRule(['id', 'dateCreated', 'dateUpdated', 'uid', 'title']),
                function ($attribute, $value, Closure $fail, Validator $validator) {
                    $found = ImportConfig::getConfigByHandle($value, true);
                    if ($found !== null && $found->uid !== $validator->getValue('uid')) {
                        $fail(t('{attribute} "{value}" has already been taken.', [
                            'attribute' => $attribute,
                            'value' => $value,
                        ]));
                    }
                },
            ],
            'settings.file' => [
                'required',
                'string',
                'max:255',
                fn ($attribute, $value, Closure $fail, Validator $validator) => self::validateFile($value, $attribute, $fail, $validator),
            ],
            'settings.className' => [
                'required',
                'string',
            ],
            'settings.transformer' => [
                'nullable',
                'string',
                'max:255',
                fn ($attribute, $value, Closure $fail, Validator $validator) => self::validateTransformer($value, $attribute, $fail, $validator),
            ],
            'settings.map' => ['array'],
        ];
    }

    /**
     * Sets the name for the importer.
     *
     * @param  string  $name  The name to set.
     */
    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Sets the handle for the importer.
     *
     * @param  string|null  $handle  The handle to be assigned.
     */
    public function handle(?string $handle): self
    {
        $this->handle = $handle;

        return $this;
    }

    /**
     * Sets the description for the importer.
     *
     * @param  string|null  $description  The description to be assigned.
     */
    public function description(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Sets the class we're importing into, e.g. Entry
     *
     * @param  string  $className  The name of the class to set.
     */
    public function className(string $className): self
    {
        $this->className = $className;

        return $this;
    }

    /**
     * Sets the path to the file that contains the data to be imported.
     *
     * @param  string|null  $file  The file name or path to set.
     */
    public function file(?string $file): self
    {
        $this->file = $file;

        return $this;
    }

    /**
     * Sets and normalizes the transformer.
     *
     * @param  string|null|BaseTransformer  $transformer  The transformer instance, class name, or null value.
     */
    public function transformer(string|null|BaseTransformer $transformer): self
    {
        $this->transformer = $this->normalizeTransformer($transformer);

        return $this;
    }

    /**
     * Sets the mapping configuration for the importer.
     *
     * @param  array  $map  The mapping configuration array.
     */
    public function map(array $map): self
    {
        $this->map = $this->unpackJson($map);

        return $this;
    }

    /**
     * Sets the criteria to be used for matching the element we're importing into
     * and returns the current instance.
     *
     * @param  array  $matchCriteria  The criteria to match against.
     */
    public function matchCriteria(array $matchCriteria): self
    {
        $this->matchCriteria = $this->unpackJson($matchCriteria);

        return $this;
    }

    /**
     * Sets the field/attribute handles that should be cleared on import when no data is provided
     * for them or the provided value is empty, and returns the current instance.
     *
     * @param  array|null  $clearableItems  The handles to mark as clearable, either as a nested map with truthy
     *                                      leaves (matching $matchCriteria's shape) or a flat list of dot-notation handles.
     */
    public function clearableItems(?array $clearableItems = null): self
    {
        if ($clearableItems !== null) {
            $clearableItems = $this->unpackJson($clearableItems);

            if (array_is_list($clearableItems)) {
                $clearableItems = Arr::undot(array_fill_keys($clearableItems, true));
            }

            $this->clearableItems = $clearableItems;
        }

        return $this;
    }

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
            $keepMissingNestedElements = $this->unpackJson($keepMissingNestedElements);

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

    /**
     * Ensure that any json values that might be present in the array are unpacked, recursively.
     */
    private function unpackJson(array $data): array
    {
        foreach ($data as &$value) {
            if (is_array($value)) {
                $value = $this->unpackJson($value);

                continue;
            }

            if (! is_string($value)) {
                continue;
            }

            $json = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $json;
            }
        }
        unset($value);

        return $data;
    }

    /**
     * Validates a provided file based on its existence, MIME type, and compatibility with
     * the application's expected data types.
     *
     * @param  mixed  $value  The file to validate, typically a path or identifier.
     * @param  string  $attribute  The name of the attribute being validated.
     * @param  Closure  $fail  A callback function to report validation failures.
     * @param  Validator  $validator  The validator instance performing the validation.
     * @param  string|null  $attributeForMessage  Optional. An alternate attribute name for error messages.
     */
    public static function validateFile(mixed $value, string $attribute, Closure $fail, Validator $validator, ?string $attributeForMessage = null): bool
    {
        if (empty($value)) {
            $fail(/* $attributeForMessage ?? */ $attribute, t('File must be provided.'));
            // $validator->errors()->add($attributeForMessage ?? $attribute, t('File must be provided.'));

            return false;
        }

        $filePath = self::resolvedFilePath($value);
        if (! file_exists($filePath)) {
            $fail($attribute, t('File “{filePath}” does not exist.', [
                'filePath' => $filePath,
            ]));

            return false;
        }

        $file = new File($filePath);
        $dataTypes = array_unique(array_filter(array_keys(Import::getAllDataTypes())));

        // validate file type (e.g. csv, json, xml)
        $newValidator = ValidatorFacade::make([
            'file' => $file,
        ], [
            'file' => ['mimes:'.implode(',', $dataTypes)],
        ]);

        if ($newValidator->fails()) {
            $fail($attribute, t('Only files with these MIME types are allowed: {mimeTypes}.', [
                'mimeTypes' => implode(', ', $dataTypes),
            ]));

            return false;
        }

        return true;
    }

    /**
     * Resolves the full file path based on the provided file alias or relative path.
     *
     * If the provided file path starts with the '@root/' alias, it retrieves the absolute
     * path using the Aliases service. Otherwise, it constructs the path by appending the
     * file to the '@root/' alias.
     *
     * @param  string|null  $file  The file alias or relative path to be resolved.
     */
    public static function resolvedFilePath(?string $file): ?string
    {
        if (is_null($file)) {
            return null;
        }

        return str_starts_with($file, '@root/') ? Aliases::get($file) : Aliases::get('@root/'.$file);
    }

    /**
     * Normalizes a transformer input into a valid BaseTransformer instance, a Closure, or null.
     *
     * This method processes various forms of input for transformers, including:
     * - Instances of BaseTransformer: These are returned as-is.
     * - Strings: These are evaluated to determine if they refer to a callable function,
     *   a PHP closure pattern, or a valid BaseTransformer class.
     * - Null values: These are handled gracefully by returning null.
     *
     * If the input defines a callable closure pattern using the `fn` syntax, it generates a Closure
     * that can evaluate the provided logic against an `ElementInterface` instance. Additionally,
     * transformer class strings are validated to ensure they refer to a valid BaseTransformer class.
     *
     * @param  string|BaseTransformer|null  $transformer  Input transformer to normalize.
     */
    public function normalizeTransformer(string|null|BaseTransformer $transformer): BaseTransformer|Closure|null
    {
        if ($transformer instanceof BaseTransformer) {
            return $transformer;
        }

        if (empty($transformer)) {
            return null;
        }

        if (preg_match('/^fn\s*\(\s*(?:\$(\w+)\s*)?\)\s*=>\s*(.+)/', $transformer, $match)) {
            $var = $match[1];
            $php = sprintf('return %s;', Str::chopStart(rtrim($match[2], ';'), 'return '));

            return function (ElementInterface $element) use ($var, $php) {
                if ($var) {
                    ${$var} = $element;
                }

                return eval($php);
            };
        }

        if (class_exists($transformer) && (new $transformer) instanceof BaseTransformer) {
            return new $transformer;
        }

        return null;
    }

    /**
     * Validates the transformer value to ensure it is either empty, a closure, or a valid class compatible with `BaseTransformer`.
     *
     * @param  mixed  $value  The value of the transformer being validated.
     * @param  string  $attribute  The name of the attribute being validated.
     * @param  Closure  $fail  The callback function to invoke when validation fails.
     * @param  Validator  $validator  The validator instance performing the validation.
     */
    public static function validateTransformer(mixed $value, string $attribute, Closure $fail, Validator $validator): bool
    {
        // if it's empty - that's fine (we'll probably use the default ElementTransformer)
        if (empty($value)) {
            return true;
        }

        // if it's an arrow function - ok
        if (preg_match('/^fn\s*\(\s*(?:\$(\w+)\s*)?\)\s*=>\s*(.+)/', (string) $value)) {
            return true;
        }

        // if it's a string - the assumption is that it's a class name with namespace (just like with elementType)
        // and we need to check if it exists and is compatible
        if (class_exists($value) && (new $value) instanceof BaseTransformer) {
            return true;
        }

        // no other options are valid
        $fail($attribute, t('Transformer has to be empty, a valid class or a closure.'));

        return false;
    }

    /**
     * Returns the transformer's class name if it's a BaseTransformer instance, otherwise null.
     */
    public function transformerAsString(): ?string
    {
        if ($this->transformer === null) {
            return $this->transformer;
        }

        if ($this->transformer instanceof BaseTransformer) {
            return $this->transformer::class;
        }

        return null;
    }

    /**
     * No-op base validator for the map setting; always returns true (subclasses override).
     *
     * @param  mixed  $value  The value of the map being validated.
     * @param  string  $attribute  The name of the attribute being validated.
     * @param  Closure  $fail  The callback function to invoke when validation fails.
     * @param  Validator  $validator  The validator instance performing the validation.
     * @param  array  $params  Additional context params for the validation.
     */
    public static function validateMap(mixed $value, string $attribute, Closure $fail, Validator $validator, array $params = []): bool
    {
        // by default this does nothing
        return true;
    }

    /**
     * Returns the names of the columns/properties/fields that we're importing into.
     */
    public function getDestinationCols(): array
    {
        return [];
    }

    /**
     * Returns the names of the columns/properties that we're importing from (the ones from the data source).
     */
    public function getSourceDataCols(): array
    {
        return [];
    }

    /**
     * No-op base implementation; subclasses override to actually perform the import.
     *
     * @param  array  $data  The data for the item being imported.
     */
    public function importItem(array $data): void
    {
        // by default, this doesn't do anything
    }
}
