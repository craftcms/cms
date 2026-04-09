<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Importers;

use Closure;
use craft\base\ElementInterface;
use craft\services\Elements;
use CraftCms\Aliases\Aliases;
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

    public protected(set) string $className;

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

    public static function displayName(): string
    {
        return t('Base Importer');
    }

    public function isEditable(): bool
    {
        return isset($this->uid);
    }

    public function getSettingsHtml(): string
    {
        return $this->settingsHtml(false);
    }

    public function getReadOnlySettingsHtml(): string
    {
        return $this->settingsHtml(true);
    }

    protected function settingsHtml(bool $readOnly): string
    {
        return template('import/_importer-types/base-importer', [
            'readOnly' => $readOnly,
            'import' => $this,
        ]);
    }

    public function isElementImport()
    {
        return is_subclass_of($this->className, ElementInterface::class);
    }

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

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function handle(?string $handle): self
    {
        $this->handle = $handle;

        return $this;
    }

    public function description(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function className(string $className): self
    {
        $this->className = $className;

        return $this;
    }

    public function file(?string $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function transformer(?string $transformer): self
    {
        $this->transformer = $this->normalizeTransformer($transformer);

        return $this;
    }

    public function map(array $map): self
    {
        $this->map = $map;

        return $this;
    }

    public function matchCriteria(array $matchCriteria): self
    {
        $this->matchCriteria = $matchCriteria;

        return $this;
    }

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

    public static function resolvedFilePath(?string $file): ?string
    {
        if (is_null($file)) {
            return null;
        }

        return str_starts_with($file, '@root/') ? Aliases::get($file) : Aliases::get('@root/'.$file);
    }

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
}
