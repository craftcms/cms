<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Importers;

use Closure;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Support\Facades\Import;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Validation\Rules\HandleRule;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator;
use League\Fractal\TransformerAbstract;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

abstract class BaseImporter
{
    public protected(set) ?string $name = null;

    public protected(set) ?string $handle = null;

    public protected(set) ?string $description = null;

    public protected(set) ?string $className = null;

    public protected(set) ?string $file = null;

    public protected(set) string|TransformerAbstract|null $transformer = null;

    public protected(set) array $map = [];

    /**
     * @var Closure|array|null
     *                         array => the column that should be used to match incoming data against existing elements;
     *                         by default, [id => id] is used, meaning elements are matched on their ID, and we expect an 'id' key in the provided data
     *                         null => don't match against existing elements; import all incoming data
     *                         Closure =>
     */
    public protected(set) Closure|array|null $matchCriteria = null;

    public ?string $uid = null;

    public bool $editable = false;

    public function __construct(?array $config = null)
    {
        if (! empty($config)) {
            $this->uid = $config['uid'] ?? null;
            $this->editable = $config['editable'] ?? false;
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
     * @return string The rendered HTML for the settings.
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
                    $found = Import::getConfigByHandle($value, true);
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
            //            'settings.transformer' => [
            //                'nullable',
            //                'string',
            //                'max:255',
            //                fn ($attribute, $value, Closure $fail, Validator $validator) => self::validateTransformer($value, $attribute, $validator),
            //            ],
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
     * @param  string|null|TransformerAbstract  $transformer  The transformer instance, class name, or null value.
     */
    public function transformer(string|null|TransformerAbstract $transformer): self
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
        $this->map = $map;

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
        $this->matchCriteria = $matchCriteria;

        return $this;
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
     * @return bool Returns true if the file is valid; otherwise, false.
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
     * @return string|null The resolved absolute file path, or null if the input is null.
     */
    public static function resolvedFilePath(?string $file): ?string
    {
        if (is_null($file)) {
            return null;
        }

        return str_starts_with($file, '@root/') ? Aliases::get($file) : Aliases::get('@root/'.$file);
    }

    /**
     * Normalizes a transformer input into a valid TransformerAbstract instance, a Closure, or null.
     *
     * This method processes various forms of input for transformers, including:
     * - Instances of TransformerAbstract: These are returned as-is.
     * - Strings: These are evaluated to determine if they refer to a callable function,
     *   a PHP closure pattern, or a valid TransformerAbstract class.
     * - Null values: These are handled gracefully by returning null.
     *
     * If the input defines a callable closure pattern using the `fn` syntax, it generates a Closure
     * that can evaluate the provided logic against an `ElementInterface` instance. Additionally,
     * transformer class strings are validated to ensure they refer to a valid TransformerAbstract class.
     *
     * @param  string|TransformerAbstract|null  $transformer  Input transformer to normalize.
     * @return TransformerAbstract|Closure|null A normalized transformer instance, closure, or null if not valid.
     */
    public function normalizeTransformer(string|null|TransformerAbstract $transformer): TransformerAbstract|Closure|null
    {
        if ($transformer instanceof TransformerAbstract) {
            return $transformer;
        }

        if (empty($transformer)) {
            return null;
        }

        if (preg_match('/^fn\s*\(\s*(?:\$(\w+)\s*)?\)\s*=>\s*(.+)/', $transformer, $match)) {
            $var = $match[1];
            $php = sprintf('return %s;', Str::removeLeft(rtrim($match[2], ';'), 'return '));

            return function (ElementInterface $element) use ($var, $php) {
                if ($var) {
                    ${$var} = $element;
                }

                return eval($php);
            };
        }

        if (class_exists($transformer) && (new $transformer) instanceof TransformerAbstract) {
            return new $transformer;
        }

        return null;
    }

    /**
     * Validates the transformer value to ensure it is either empty, a closure, or a valid class compatible with `TransformerAbstract`.
     *
     * @param  mixed  $value  The value of the transformer being validated.
     * @param  string  $attribute  The name of the attribute being validated.
     * @param  Closure  $fail  The callback function to invoke when validation fails.
     * @param  Validator  $validator  The validator instance performing the validation.
     * @return bool Returns true if the transformer value is valid; otherwise, false.
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
        if (class_exists($value) && (new $value) instanceof TransformerAbstract) {
            return true;
        }

        // no other options are valid
        $fail($attribute, t('Transformer has to be empty, a valid class or a closure.'));

        return false;
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

    public function importItem(array $data): void
    {
        // by default, this doesn't do anything
    }
}
