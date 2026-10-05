<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Importers;

use Closure;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Import\ElementImporter;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Import\Data\CompoundMappingColumn;
use CraftCms\Cms\Import\Data\ImportStep;
use CraftCms\Cms\Import\Data\MappingColumn;
use CraftCms\Cms\Import\Data\SourceColumn;
use CraftCms\Cms\Import\Transformers\BaseTransformer;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Import;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\Facades\Security;
use CraftCms\Cms\Support\File as FileHelper;
use CraftCms\Cms\Support\ImportHelper;
use CraftCms\Cms\Support\Json as JsonSupport;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use CraftCms\UrlValidator\UrlValidationException;
use CraftCms\UrlValidator\UrlValidator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mime\MimeTypes;
use Throwable;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
abstract class BaseImporter
{
    public protected(set) ?string $source = null;

    public protected(set) string|BaseTransformer|null $transformer = null;

    public protected(set) ?int $batchSize = null;

    /**
     * @var array<mixed> The mapping configuration, keyed by what to import into.
     */
    public protected(set) array $map = [];

    /**
     * @var array<mixed>|null
     *
     * array => key is what to import into (e.g. a field handle or attribute name)
     *  value is the name/key/property from the incoming data;
     *  example: ['plainText' => 'myPlainText'] means that you have a plainText field in you field layout or column in your table
     *    and you want to import the value of 'myPlainText' column/property from the incoming csv/json/xml;
     *  the array can be multidimensional;
     *  the value should be the name/key/property from the incoming data - it should not be adjusted for mapping at this stage;
     *
     *  null => don't match against existing elements; import all incoming data as new
     */
    public protected(set) ?array $matchCriteria = null;

    /**
     * @var array<mixed>|null
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

    public ?string $uid = null;

    /**
     * @var list<Closure> Callbacks to run once the item being imported has been imported.
     */
    private array $afterItemImportedCallbacks = [];

    /**
     * Sets `$this->uid` from a config array if provided.
     *
     * @param  array<string, mixed>|null  $config  Optional config array, potentially containing a `uid` key.
     */
    public function __construct(?array $config = null)
    {
        if (! empty($config)) {
            $this->uid = $config['uid'] ?? null;
            $this->source($config['source'] ?? null);
            $this->transformer($config['transformer'] ?? null);
            $this->batchSize($config['batchSize'] ?? null);

            $settings = $config['settings'] ?? [];
            if (is_string($settings)) {
                $settings = JsonSupport::decode($settings);
            }

            foreach ($settings as $setting => $value) {
                if (method_exists($this, $setting)) {
                    $reflection = new \ReflectionMethod($this, $setting);
                    if ($reflection->isPublic()) {
                        $this->{$setting}($value);
                    }
                }
            }

            // an element type whose layout isn't chosen through a setting resolves it here, so a
            // step that has never been saved still knows what it's importing into
            if ($this instanceof ElementImporter && $this->fieldLayout === null) {
                $this->resolveDefaultFieldLayout();
            }
        }
    }

    /**
     * Returns the fixed element type FQCN this importer subclass targets.
     */
    abstract public static function targetClass(): string;

    abstract public static function create(): self;

    /**
     * Returns the display name for the importer.
     */
    abstract public static function displayName(): string;

    /**
     * Defines the type-specific settings nodes and context for this importer form.
     *
     * @return array{context?: FormContext, nodes?: list<Node>}
     */
    abstract public function settingsForm(FormContext $context): array;

    /**
     * Renders the importer-specific part of the settings form.
     *
     * @param  array<string, mixed>  $settings
     */
    abstract public function refreshSettingsForm(array $settings): void;

    /**
     * Gives importers a chance to store their specific settings.
     *
     * @param  array<string, mixed>  $settings
     */
    abstract public function storeSettings(array $settings): void;

    /**
     * Returns an array of importer-specific settings.
     *
     * @return array<string, mixed>
     */
    abstract public function getSettings(): array;

    /**
     * Specifies a default transformer that the importer should use if none is provided.
     */
    abstract public static function getDefaultTransformer(): ?string;

    /**
     * Sets the path or URL of the source that contains the data to be imported.
     * The value is stored as given; it's checked by `validate()`.
     *
     * @param  string|null  $source  The source alias, path or URL to set.
     */
    public function source(?string $source): self
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Sets and normalizes the transformer.
     *
     * @param  string|null|BaseTransformer  $transformer  The transformer instance, class name, or null value.
     */
    public function transformer(string|null|BaseTransformer $transformer): self
    {
        $transformer ??= static::getDefaultTransformer();
        $this->transformer = self::normalizeTransformer($transformer);

        return $this;
    }

    public function batchSize(?int $batchSize): self
    {
        $this->batchSize = $batchSize;

        return $this;
    }

    /**
     * Sets the mapping configuration for the importer.
     *
     * @param  array<mixed>  $map  The mapping configuration array.
     */
    public function map(array $map): self
    {
        $this->map = ImportHelper::decodeRecursive($map);

        return $this;
    }

    /**
     * Sets the criteria to be used for matching the element we're importing into
     * and returns the current instance.
     *
     * @param  array<mixed>|null  $matchCriteria  The criteria to match against.
     */
    public function matchCriteria(?array $matchCriteria): self
    {
        if ($matchCriteria === null) {
            $this->matchCriteria = null;

            return $this;
        }

        $this->matchCriteria = ImportHelper::decodeRecursive($matchCriteria);

        return $this;
    }

    /**
     * Sets the field/attribute handles that should be cleared on import when no data is provided
     * for them or the provided value is empty, and returns the current instance.
     *
     * @param  array<mixed>|null  $clearableItems  The handles to mark as clearable, either as a nested map with truthy
     *                                             leaves (matching $matchCriteria's shape) or a flat list of dot-notation handles.
     */
    public function clearableItems(?array $clearableItems = null): self
    {
        if ($clearableItems !== null) {
            $clearableItems = ImportHelper::decodeRecursive($clearableItems);

            if (array_is_list($clearableItems)) {
                $clearableItems = Arr::undot(array_fill_keys($clearableItems, true));
            }

            $this->clearableItems = $clearableItems;
        }

        return $this;
    }

    /**
     * Defines the validation rules for an import step using this importer.
     * URL hostname resolution (DNS lookup) can be skipped via `$resolveHost`.
     *
     * @param  bool  $resolveHost  Whether to resolve a URL's hostname when validating the source.
     * @return array<string, Closure|list<string|Closure>>
     */
    public static function getRules(bool $resolveHost = true): array
    {
        return array_merge([
            'source' => [
                'required',
                'string',
                'max:2048',
                function (string $attribute, mixed $value, Closure $fail) use ($resolveHost) {
                    // a non-string value is reported by the `string` rule
                    if (! is_string($value)) {
                        return;
                    }

                    $error = self::sourceError($value, $resolveHost);

                    if ($error !== null) {
                        $fail($error);
                    }
                },
            ],
            'transformer' => [
                'nullable',
                'string',
                'max:255',
                fn ($attribute, $value, Closure $fail, Validator $validator) => self::validateTransformer($value, $attribute, $fail, $validator),
            ],
            'batchSize' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ], static::getSettingsRules());
    }

    /**
     * Defines the validation rules for the importer's execution settings, independent of
     * whether the importer is ever persisted/named (e.g. an ad-hoc CLI-built importer).
     *
     * @return array<string, Closure|list<string|Closure>>
     */
    public static function getSettingsRules(): array
    {
        return [
            'settings.map' => ['array'],
            'settings.matchCriteria' => ['nullable', 'array'],
            'settings.clearableItems' => ['nullable', 'array'],
        ];
    }

    /**
     * Returns the importer's current state as a step, the shape the import plan edit screen holds.
     */
    public function toImportStep(): ImportStep
    {
        $data = $this->toArrayData();

        return new ImportStep(
            uid: $data['uid'],
            type: $data['type'],
            source: $data['source'],
            transformer: $data['transformer'],
            batchSize: $data['batchSize'],
            settings: $data['settings'],
        );
    }

    /**
     * Builds the step data array validated by `getRules()`/`getSettingsRules()`, from the importer's current state.
     *
     * @return array{uid: string|null, type: class-string<static>, source: string|null, transformer: string|null, batchSize: int|null, settings: array<string, mixed>}
     */
    public function toArrayData(): array
    {
        return [
            'uid' => $this->uid,
            'type' => static::class,
            'source' => $this->source,
            'transformer' => $this->transformer instanceof BaseTransformer ? $this->transformer::class : $this->transformer,
            'batchSize' => $this->batchSize,
            'settings' => [
                'map' => $this->map,
                'matchCriteria' => $this->matchCriteria,
                'clearableItems' => $this->clearableItems,
            ],
        ];
    }

    /**
     * Validates the importer's full state.
     *
     * @throws ValidationException
     */
    public function validate(): void
    {
        ValidatorFacade::make($this->toArrayData(), static::getRules())->validate();
    }

    /**
     * Validates only the importer's execution settings.
     *
     * @throws ValidationException
     */
    public function validateSettings(): void
    {
        ValidatorFacade::make($this->toArrayData(), static::getSettingsRules())->validate();
    }

    /**
     * Returns an error message if the source path or URL can't be used for import, or null if it can.
     * Hostname resolution (DNS lookup) can be skipped for URLs via `$resolveHost`.
     *
     * @param  string|null  $source  The source alias, path or URL to check.
     * @param  bool  $resolveHost  Whether to resolve a URL's hostname.
     */
    public static function sourceError(?string $source, bool $resolveHost = true): ?string
    {
        if (empty($source)) {
            return t('Source must be provided.');
        }

        if (! Url::isAbsoluteUrl($source) && ! str_starts_with($source, '@') && new Filesystem()->isAbsolutePath($source)) {
            return t('File paths must be relative to the project root or start with an alias.');
        }

        try {
            $sourcePath = self::resolvedSourcePath($source);
        } catch (InvalidArgumentException) {
            return t('The alias in “{source}” isn’t defined.', ['source' => $source]);
        }

        if (Url::isValidUrl($sourcePath)) {
            $urlValidator = static::urlValidator();

            if (! $resolveHost) {
                if (! $urlValidator->validateScheme($sourcePath) || ! $urlValidator->validateHostname($sourcePath)) {
                    return t('URL “{url}” is not permitted.', ['url' => $sourcePath]);
                }
            } else {
                try {
                    $urlValidator->validate($sourcePath);
                } catch (UrlValidationException) {
                    return t('URL “{url}” is not permitted.', ['url' => $sourcePath]);
                }
            }

            // the source's type can only be determined from the response (e.g. export.php can return JSON),
            // so it's checked once the file is fetched
            return null;
        }

        // reject any other scheme (e.g. data:, glob://, phar://) so PHP's stream wrappers can't be used
        if (Url::isAbsoluteUrl($sourcePath) || ! self::isFilepathAllowed($sourcePath)) {
            return t('Access to this file ({sourcePath}) is not permitted.', [
                'sourcePath' => $sourcePath,
            ]);
        }

        if (! is_file($sourcePath) || ! is_readable($sourcePath)) {
            return t('File “{sourcePath}” does not exist.', [
                'sourcePath' => $sourcePath,
            ]);
        }

        return self::getExtensionError($sourcePath) ?? self::getContentError($sourcePath);
    }

    /**
     * Returns whether the given source value resolves to a URL.
     *
     * @param  string|null  $source  The source alias, path or URL to check.
     */
    public static function isRemoteSource(?string $source): bool
    {
        $sourcePath = self::resolvedSourcePath($source);

        return $sourcePath !== null && Url::isValidUrl($sourcePath);
    }

    /**
     * Downloads the importer's remote file into the given directory, checks its type and returns its local path.
     *
     * @param  string  $directory  The directory to download the file into.
     *
     * @throws InvalidArgumentException
     */
    public function downloadFile(string $directory): string
    {
        $url = (string) self::resolvedSourcePath($this->source);
        FileHelper::makeDirectory($directory);
        $tempPath = $directory.DIRECTORY_SEPARATOR.Str::uuid()->toString();

        try {
            $response = AssetsHelper::downloadUrl(static::urlValidator(), $url, $tempPath);
        } catch (Throwable $e) {
            @unlink($tempPath);

            throw new InvalidArgumentException(t('Unable to download the file from “{url}”.', ['url' => $url]), previous: $e);
        }

        $dataType = self::getDataTypeFromContentType($response->header('Content-Type'), $url);

        if ($dataType === null) {
            @unlink($tempPath);

            throw new InvalidArgumentException(t('Unable to determine the type of the file at “{url}”.', ['url' => $url]));
        }

        // the readers pick the data type from the extension
        $filePath = "$tempPath.$dataType";
        rename($tempPath, $filePath);

        $error = self::getContentError($filePath, $url);

        if ($error !== null) {
            @unlink($filePath);

            throw new InvalidArgumentException($error);
        }

        return $filePath;
    }

    /**
     * Calls the callback with a local path to the importer's file.
     * A remote file is downloaded to a temp file first and deleted once the callback returns.
     *
     * @param  Closure  $callback  The callback that receives the local file path.
     *
     * @throws InvalidArgumentException
     */
    public function withLocalFile(Closure $callback): mixed
    {
        if (! self::isRemoteSource($this->source)) {
            return $callback(self::resolvedSourcePath($this->source));
        }

        $filePath = $this->downloadFile(Path::temp());

        try {
            return $callback($filePath);
        } finally {
            @unlink($filePath);
        }
    }

    /**
     * Returns an error message if the source's extension isn't one of the available data types, or null if it is.
     */
    private static function getExtensionError(string $sourcePath): ?string
    {
        if (Import::getDataTypeFromExtension($sourcePath) !== null) {
            return null;
        }

        return t('Only files of these types are allowed: {fileTypes}.', [
            'fileTypes' => implode(', ', array_keys(Import::getAllDataTypes())),
        ]);
    }

    /**
     * Returns an error message if a local source's contents don't match the data type of its extension, or null if they do
     * (or if the extension isn't a data type, which `getExtensionError()` reports).
     */
    private static function getContentError(string $sourcePath, ?string $url = null): ?string
    {
        $dataType = Import::getDataTypeFromExtension($sourcePath);

        if ($dataType === null) {
            return null;
        }

        // CSV has no signature, so its contents are often detected as plain text
        $allowedExtensions = $dataType === 'csv' ? ['csv', 'txt'] : [$dataType];

        $contentsMatch = ValidatorFacade::make(
            ['source' => new File($sourcePath)],
            ['source' => ['mimes:'.implode(',', $allowedExtensions)]],
        )->passes();

        if (! $contentsMatch) {
            return t('The contents of “{source}” don’t match its type ({dataType}).', [
                'source' => $url ?? $sourcePath,
                'dataType' => $dataType,
            ]);
        }

        return null;
    }

    /**
     * Returns the data type of a remote source, based on the response's content type, falling back to the URL path's extension,
     * or null if neither is one of the available data types.
     */
    private static function getDataTypeFromContentType(string $contentType, string $url): ?string
    {
        $dataTypes = Import::getAllDataTypes();
        $mimeType = strtolower(trim(explode(';', $contentType)[0]));

        if ($mimeType !== '') {
            foreach (MimeTypes::getDefault()->getExtensions($mimeType) as $extension) {
                if (isset($dataTypes[$extension])) {
                    return $extension;
                }
            }
        }

        return Import::getDataTypeFromExtension((string) parse_url($url, PHP_URL_PATH));
    }

    /**
     * Returns the validator used to check import URLs.
     */
    public static function urlValidator(?callable $resolver = null): UrlValidator
    {
        return new UrlValidator($resolver, [
            'ipv4FilterFlags' => FILTER_FLAG_NO_RES_RANGE,
            'ipv6FilterFlags' => FILTER_FLAG_NO_RES_RANGE,
        ]);
    }

    private static function isFilepathAllowed(string $filePath): bool
    {
        // disallow if the filename starts with a dot
        $basename = basename($filePath);
        if (str_starts_with($basename, '.')) {
            return false;
        }

        // resolve symlinks and relative segments before checking the location
        $realPath = realpath($filePath);

        // disallow if the $filePath is within one of the restricted directories
        if (Security::isRestrictedDir($filePath) || ($realPath !== false && Security::isRestrictedDir($realPath))) {
            return false;
        }

        return true;
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

        if (self::normalizeTransformer($value) === null) {
            $fail($attribute, t('Transformer has to be empty or a valid transformer class.'));

            return false;
        }

        return true;
    }

    /**
     * No-op base validator for the map setting; always returns true (subclasses override).
     *
     * @param  mixed  $value  The value of the map being validated.
     * @param  string  $attribute  The name of the attribute being validated.
     * @param  Closure  $fail  The callback function to invoke when validation fails.
     * @param  Validator  $validator  The validator instance performing the validation.
     * @param  array<string, mixed>  $params  Additional context params for the validation.
     */
    public static function validateMap(mixed $value, string $attribute, Closure $fail, Validator $validator, array $params = []): bool
    {
        // by default this does nothing
        return true;
    }

    /**
     * Returns the columns/properties/fields that we're importing into.
     *
     * @return list<MappingColumn|CompoundMappingColumn>
     */
    public function getDestinationCols(): array
    {
        return [];
    }

    /**
     * Returns the names of the columns/properties that we're importing from (the ones from the data source),
     * or null if the data couldn't be parsed.
     *
     * @return list<SourceColumn>|null
     */
    public function getSourceDataCols(): ?array
    {
        return [];
    }

    /**
     * No-op base implementation; subclasses override to actually perform the import
     * and return the element or model the data was imported into, if any.
     *
     * @param  array<string, mixed>  $data  The data for the item being imported.
     */
    public function importItem(array $data): ElementInterface|Model|null
    {
        // by default, this doesn't do anything
        return null;
    }

    /**
     * Queues a callback to run once the item being imported has been imported (saved, or skipped as unchanged).
     *
     * Use it for side effects that should only happen if the item goes through, e.g. from a field import handler.
     * The callback receives the imported element or model; it’s discarded if importing the item fails.
     *
     * @param  Closure(ElementInterface|Model|null): void  $callback  The callback.
     */
    public function afterItemImported(Closure $callback): void
    {
        $this->afterItemImportedCallbacks[] = $callback;
    }

    /**
     * Runs and clears the callbacks queued for the item that has just been imported.
     *
     * @internal
     *
     * @param  ElementInterface|Model|null  $item  The imported element or model.
     */
    public function runAfterItemImportedCallbacks(ElementInterface|Model|null $item): void
    {
        $callbacks = $this->afterItemImportedCallbacks;
        $this->afterItemImportedCallbacks = [];

        foreach ($callbacks as $callback) {
            $callback($item);
        }
    }

    /**
     * Clears the callbacks queued for an item that failed to import.
     *
     * @internal
     */
    public function discardAfterItemImportedCallbacks(): void
    {
        $this->afterItemImportedCallbacks = [];
    }

    /**
     * Resolves the full source path or URL based on the provided value.
     *
     * URLs and aliases are resolved via the Aliases service as they are.
     * Any other value is treated as a path relative to the '@root' alias.
     *
     * @param  string|null  $source  The URL, source alias or relative path to be resolved.
     */
    public static function resolvedSourcePath(?string $source): ?string
    {
        if (is_null($source)) {
            return null;
        }

        if (Url::isAbsoluteUrl($source) || str_starts_with($source, '@')) {
            return Aliases::get($source);
        }

        return Aliases::get('@root/'.$source);
    }

    /**
     * Normalizes a transformer input into a BaseTransformer instance, or null.
     *
     * A string is only accepted as the name of a BaseTransformer subclass, which is checked
     * before the class is instantiated.
     */
    private static function normalizeTransformer(string|null|BaseTransformer $transformer): ?BaseTransformer
    {
        if ($transformer instanceof BaseTransformer) {
            return $transformer;
        }

        if (empty($transformer) || ! is_subclass_of($transformer, BaseTransformer::class)) {
            return null;
        }

        return new $transformer;
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

    public static function isElementImporter(): bool
    {
        return false;
    }

    /**
     * Returns whether the current transformer is the default one for the element type.
     */
    public function usesDefaultTransformer(): bool
    {
        $currentTransformer = $this->transformer;
        $defaultTransformer = static::getDefaultTransformer();

        // if they're simply the same - they're the same
        if ($currentTransformer === $defaultTransformer) {
            return true;
        }

        // if the current transformer is an object and the class matches the default one - they're the same
        if ($currentTransformer instanceof BaseTransformer && $currentTransformer::class === $defaultTransformer) {
            return true;
        }

        return false;
    }
}
