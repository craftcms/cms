<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Importers;

use Closure;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Import\ElementImporter;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
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
use GuzzleHttp\RequestOptions;
use GuzzleHttp\TransferStats;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mime\MimeTypes;
use Throwable;

use function CraftCms\Cms\t;

abstract class BaseImporter
{
    public protected(set) ?string $file = null;

    public protected(set) string|BaseTransformer|null $transformer = null;

    public protected(set) ?int $batchSize = null;

    public protected(set) array $map = [];

    /**
     * @var Closure|array|null
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
            $this->file($config['file']);
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
     */
    abstract public function settingsForm(FormContext $context): array;

    /**
     * Renders the importer-specific part of the settings form.
     */
    abstract public function refreshSettingsForm(array $settings): void;

    /**
     * Gives importers a chance to store their specific settings.
     */
    abstract public function storeSettings(array $settings): void;

    /**
     * Returns an array of importer-specific settings.
     */
    abstract public function getSettings(): array;

    /**
     * Specifies a default transformer that the importer should use if none is provided.
     */
    abstract public static function getDefaultTransformer(): ?string;

    /**
     * Sets the path or URL of the file that contains the data to be imported.
     * The value is stored as given; it's checked by `validate()`.
     *
     * @param  string|null  $file  The file alias, path or URL to set.
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
     * @param  array  $map  The mapping configuration array.
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
     * @param  array|null  $matchCriteria  The criteria to match against.
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
     * @param  array|null  $clearableItems  The handles to mark as clearable, either as a nested map with truthy
     *                                      leaves (matching $matchCriteria's shape) or a flat list of dot-notation handles.
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
     */
    public static function getRules(): array
    {
        return array_merge([
            'file' => [
                'required',
                'string',
                'max:255',
                fn ($attribute, $value, Closure $fail, Validator $validator) => self::validateFile($value, $attribute, $fail, $validator),
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
     * Builds the step data array validated by `getRules()`/`getSettingsRules()`, from the importer's current state.
     */
    public function toArrayData(): array
    {
        return [
            'uid' => $this->uid,
            'type' => static::class,
            'file' => $this->file,
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
     * Validates a provided file path or URL based on its location, existence and MIME type.
     *
     * @param  mixed  $value  The file path, alias or URL to validate.
     * @param  string  $attribute  The name of the attribute being validated.
     * @param  Closure  $fail  A callback function to report validation failures.
     * @param  Validator  $validator  The validator instance performing the validation.
     */
    public static function validateFile(mixed $value, string $attribute, Closure $fail, Validator $validator): bool
    {
        $error = self::fileError($value);

        if ($error !== null) {
            $fail($attribute, $error);

            return false;
        }

        return true;
    }

    /**
     * Returns an error message if the file path or URL can't be used for import, or null if it can.
     * Hostname resolution (DNS lookup) can be skipped for URLs via `$resolveHost`.
     */
    private static function fileError(?string $file, bool $resolveHost = true): ?string
    {
        if (empty($file)) {
            return t('File must be provided.');
        }

        if (! Url::isAbsoluteUrl($file) && ! str_starts_with($file, '@') && new Filesystem()->isAbsolutePath($file)) {
            return t('File paths must be relative to the project root or start with an alias.');
        }

        $filePath = self::resolvedFilePath($file);

        if (self::isRemoteFile($file)) {
            $urlValidator = static::urlValidator();

            if (! $resolveHost) {
                if (! $urlValidator->validateScheme($filePath) || ! $urlValidator->validateHostname($filePath)) {
                    return t('URL “{url}” is not permitted.', ['url' => $filePath]);
                }
            } else {
                try {
                    $urlValidator->validate($filePath);
                } catch (UrlValidationException) {
                    return t('URL “{url}” is not permitted.', ['url' => $filePath]);
                }
            }

            // the file's type can only be determined from the response, so it's checked once the file is fetched;
            // an extension in the URL's path has to be one of the data types though
            $path = (string) parse_url($filePath, PHP_URL_PATH);

            return pathinfo($path, PATHINFO_EXTENSION) === '' ? null : self::getExtensionError($path);
        }

        // reject any other scheme (e.g. data:, glob://, phar://) so PHP's stream wrappers can't be used
        if (Url::isAbsoluteUrl($filePath) || ! self::isFilepathAllowed($filePath)) {
            return t('Access to this file ({filePath}) is not permitted.', [
                'filePath' => $filePath,
            ]);
        }

        if (! is_file($filePath) || ! is_readable($filePath)) {
            return t('File “{filePath}” does not exist.', [
                'filePath' => $filePath,
            ]);
        }

        return self::getExtensionError($filePath) ?? self::getContentError($filePath);
    }

    /**
     * Returns whether the given file value resolves to a URL.
     *
     * @param  string|null  $file  The file alias, path or URL to check.
     */
    public static function isRemoteFile(?string $file): bool
    {
        $filePath = self::resolvedFilePath($file);

        return $filePath !== null && Url::isValidUrl($filePath);
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
        $url = (string) self::resolvedFilePath($this->file);
        FileHelper::makeDirectory($directory);
        $tempPath = $directory.DIRECTORY_SEPARATOR.Str::uuid()->toString();

        try {
            $response = self::fetchUrl($url, $tempPath);
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
        if (! self::isRemoteFile($this->file)) {
            return $callback(self::resolvedFilePath($this->file));
        }

        $filePath = $this->downloadFile(Path::temp());

        try {
            return $callback($filePath);
        } finally {
            @unlink($filePath);
        }
    }

    /**
     * Downloads a URL to the given path, pinning the connection to the IPs the URL was validated against.
     *
     * @throws InvalidArgumentException
     */
    private static function fetchUrl(string $url, string $destination): Response
    {
        $urlValidator = static::urlValidator();

        // validate the URL and resolve it to a known-good set of IPs before opening any connection (guards against SSRF and DNS rebinding)
        try {
            $ips = $urlValidator->validate($url);
        } catch (UrlValidationException $e) {
            throw new InvalidArgumentException("$url is invalid.", previous: $e);
        }

        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT)
            ?? (strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https' ? 443 : 80);

        return Http::create()->withOptions([
            RequestOptions::ALLOW_REDIRECTS => false,
            RequestOptions::SINK => $destination,
            // pin the connection to the validated IPs, so cURL doesn't re-resolve the hostname to a different address
            'curl' => [
                CURLOPT_RESOLVE => ["$host:$port:".implode(',', $ips)],
            ],
            RequestOptions::ON_STATS => function (TransferStats $stats) use ($url, $urlValidator) {
                // validate the IP, in case the cURL handler isn't in use (so CURLOPT_RESOLVE was ignored)
                $ip = $stats->getHandlerStat('primary_ip');
                if ($ip && ! $urlValidator->validateIp($ip)) {
                    throw new InvalidArgumentException("$url is invalid.");
                }
            },
        ])->get($url)->throw();
    }

    /**
     * Returns an error message if the file's extension isn't one of the available data types, or null if it is.
     */
    private static function getExtensionError(string $filePath): ?string
    {
        if (Import::getDataTypeFromExtension($filePath) !== null) {
            return null;
        }

        return t('Only files with these MIME types are allowed: {mimeTypes}.', [
            'mimeTypes' => implode(', ', array_keys(Import::getAllDataTypes())),
        ]);
    }

    /**
     * Returns an error message if a local file's contents don't match the data type of its extension, or null if they do
     * (or if the extension isn't a data type, which `getExtensionError()` reports).
     */
    private static function getContentError(string $filePath, ?string $url = null): ?string
    {
        $dataType = Import::getDataTypeFromExtension($filePath);

        if ($dataType === null) {
            return null;
        }

        // CSV has no signature, so its contents are often detected as plain text
        $allowedExtensions = $dataType === 'csv' ? ['csv', 'txt'] : [$dataType];

        $contentsMatch = ValidatorFacade::make(
            ['file' => new File($filePath)],
            ['file' => ['mimes:'.implode(',', $allowedExtensions)]],
        )->passes();

        if (! $contentsMatch) {
            return t('The contents of “{source}” don’t match its type ({dataType}).', [
                'source' => $url ?? $filePath,
                'dataType' => $dataType,
            ]);
        }

        return null;
    }

    /**
     * Returns the data type of a remote file, based on the response's content type, falling back to the URL path's extension,
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
    protected static function urlValidator(?callable $resolver = null): UrlValidator
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
            $fail($attribute, t('Transformer has to be empty, a valid class or a closure.'));

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
     * No-op base implementation; subclasses override to actually perform the import
     * and return the element or model the data was imported into, if any.
     *
     * @param  array  $data  The data for the item being imported.
     */
    public function importItem(array $data): ElementInterface|Model|null
    {
        // by default, this doesn't do anything
        return null;
    }

    /**
     * Returns whether a file is specified and points to an existing, importable file or an allowed URL.
     * It's used e.g. to determine whether an "Edit mapping" button can be shown.
     * URL hostnames are not resolved here.
     *
     * @param  string|null  $file  The file alias, path or URL to check.
     */
    public static function isFileValid(?string $file): bool
    {
        return self::fileError($file, resolveHost: false) === null;
    }

    /**
     * Resolves the full file path or URL based on the provided value.
     *
     * URLs and aliases are resolved via the Aliases service as they are.
     * Any other value is treated as a path relative to the '@root' alias.
     *
     * @param  string|null  $file  The URL, file alias or relative path to be resolved.
     */
    public static function resolvedFilePath(?string $file): ?string
    {
        if (is_null($file)) {
            return null;
        }

        if (Url::isAbsoluteUrl($file) || str_starts_with($file, '@')) {
            return Aliases::get($file);
        }

        return Aliases::get('@root/'.$file);
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
    private static function normalizeTransformer(string|null|BaseTransformer $transformer): BaseTransformer|Closure|null
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
