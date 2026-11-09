<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Importers;

use Closure;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Component\Contracts\Chippable;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Import\ElementImporter;
use CraftCms\Cms\Field\Contracts\ImportableElementContainerFieldInterface;
use CraftCms\Cms\Import\Data\CompoundMappingColumn;
use CraftCms\Cms\Import\Data\ImportStep;
use CraftCms\Cms\Import\Data\MappingColumn;
use CraftCms\Cms\Import\Data\SourceColumn;
use CraftCms\Cms\Import\DataTypes\DataTypeInterface;
use CraftCms\Cms\Import\ImportHelper;
use CraftCms\Cms\Import\Transformers\BaseTransformer;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Import;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\Facades\Security;
use CraftCms\Cms\Support\File as FileHelper;
use CraftCms\Cms\Support\Json as JsonSupport;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\UrlValidator\UrlValidationException;
use CraftCms\UrlValidator\UrlValidator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mime\MimeTypes;
use Throwable;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Sandbox\SecurityError;

use function CraftCms\Cms\renderSandboxedObjectTemplate;
use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
abstract class BaseImporter
{
    public protected(set) ?string $source = null;

    public protected(set) ?BaseTransformer $transformer = null;

    public protected(set) ?int $batchSize = null;

    /**
     * Whether the step runs when its import plan does. Disabled steps stay in the plan but are skipped.
     */
    public protected(set) bool $enabled = true;

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
     *  for convenience, registered import plans may instead provide a flat list of dot-notation handles
     *  (e.g. ['heading', 'body']), which gets normalized into the nested truthy-leaf shape;
     *
     *  null => nothing is cleared; missing/empty incoming values are left untouched
     */
    public protected(set) ?array $clearableItems = null;

    /**
     * @var array<mixed>|null
     *
     * array => keyed like $map; the value is the object template to render for each `$map` leaf set to
     *  `ImportHelper::TEMPLATE_RULE`, e.g. `['title' => '{firstName} {lastName}']`;
     *  the template is rendered against the incoming data (or, inside a nested list, against the incoming data
     *  with that list swapped for the item being mapped), so `{blocks.heading}` reads the current block's heading;
     *  templates whose `$map` leaf isn't `ImportHelper::TEMPLATE_RULE` are ignored;
     *
     *  null => no templates
     */
    public protected(set) ?array $templates = null;

    /**
     * @var array<mixed>|null
     *
     * array => keyed like $map; the value stands in for a field's incoming value when that's missing or empty,
     *  e.g. `['summary' => 'Coming soon']`; an empty default leaves the field to `$clearableItems` as usual;
     *
     *  null => no defaults
     */
    public protected(set) ?array $defaultValues = null;

    /**
     * @var class-string<DataTypeInterface>|null The data type to read the source as, when more than one data type
     *                                           claims its extension and its contents match more than one of them,
     *                                           or when it's a URL whose extension isn't claimed by any.
     */
    public protected(set) ?string $dataType = null;

    /**
     * @var array<string, mixed> The settings the source's data type reads it with, keyed by setting name (see
     *                           `DataTypeInterface::getSettingsRules()`). The reserved `type` key holds the data type
     *                           they were made for; settings made for another data type are ignored.
     */
    public protected(set) array $dataTypeSettings = [];

    public ?string $uid = null;

    /**
     * @var array<string, class-string<DataTypeInterface>|null> The data types sources are definitely read as, keyed by source and chosen data type.
     */
    private array $resolvedDataTypes = [];

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
        if (empty($config)) {
            return;
        }

        $this->uid = $config['uid'] ?? null;
        $this->source($config['source'] ?? null);
        $this->transformer($config['transformer'] ?? null);
        $this->batchSize($config['batchSize'] ?? null);
        $this->enabled((bool) ($config['enabled'] ?? true));

        $settings = $config['settings'] ?? [];
        if (is_string($settings)) {
            $settings = JsonSupport::decode($settings);
        }

        // only declared settings, each applied through the setter of the same name; the config can
        // come from a request, so nothing else on the importer may be called this way
        $settingNames = static::settingNames();

        foreach ($settings as $setting => $value) {
            if (in_array($setting, $settingNames, true) && method_exists($this, $setting)) {
                $this->{$setting}($value);
            }
        }

        // an element type whose layout isn't chosen through a setting resolves it here, so a
        // step that has never been saved still knows what it's importing into
        if ($this instanceof ElementImporter && $this->fieldLayout === null) {
            $this->resolveDefaultFieldLayout();
        }
    }

    /**
     * Returns the fixed element type FQCN this importer subclass targets.
     */
    abstract public static function targetClass(): string;

    /**
     * Returns the display name for the importer.
     */
    abstract public static function displayName(): string;

    /**
     * Defines the type-specific settings nodes for this importer, scoped under `settings` in the step's UI.
     *
     * @return list<Node>
     */
    abstract public function settingsUi(): array;

    /**
     * Applies the posted settings when the importer-specific part of the step's UI refreshes.
     *
     * @param  array<string, mixed>  $settings
     */
    abstract public function refreshSettingsUi(array $settings): void;

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
     * Sets whether the step runs when its import plan does.
     */
    public function enabled(bool $enabled): self
    {
        $this->enabled = $enabled;

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
     * Sets the object templates used by the `$map` leaves set to `ImportHelper::TEMPLATE_RULE`, and returns
     * the current instance.
     *
     * @param  array<mixed>|null  $templates
     */
    public function templates(?array $templates = null): self
    {
        $this->templates = $templates;

        return $this;
    }

    /**
     * Sets the values that stand in for missing or empty incoming values, keyed like `$map`, and returns the
     * current instance.
     *
     * @param  array<mixed>|null  $defaultValues
     */
    public function defaultValues(?array $defaultValues = null): self
    {
        $this->defaultValues = $defaultValues === null ? null : ImportHelper::decodeRecursive($defaultValues);

        return $this;
    }

    /**
     * Sets the data type to read the source as, when its contents could be read as more than one.
     *
     * @param  class-string<DataTypeInterface>|null  $dataType
     */
    public function dataType(?string $dataType): self
    {
        $this->dataType = $dataType ?: null;

        return $this;
    }

    /**
     * Sets the settings the source's data type reads it with.
     *
     * @param  array<string, mixed>|string|null  $settings  The settings, or a JSON object of them.
     */
    public function dataTypeSettings(array|string|null $settings): self
    {
        if (is_string($settings)) {
            $settings = JsonSupport::decode($settings);
        }

        $this->dataTypeSettings = is_array($settings) ? $settings : [];

        return $this;
    }

    /**
     * Returns a value for a field or attribute that takes more than one value: a string split into the values it
     * holds, if the data type the source is definitely read as splits it (e.g. CSV with a list separator), or else
     * the value as it is.
     */
    public function listValue(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        // resolving it can read the start of the file, so it's only done once per source and choice
        $key = "$this->source\0$this->dataType";

        if (! array_key_exists($key, $this->resolvedDataTypes)) {
            $this->resolvedDataTypes[$key] = self::resolvedDataType($this->source, $this->dataType);
        }

        $dataType = $this->resolvedDataTypes[$key];

        if ($dataType === null) {
            return $value;
        }

        return $dataType::splitList($value, $this->dataTypeSettingsFor($dataType)) ?? $value;
    }

    /**
     * Returns the settings a data type reads the source with: those named by its `getSettingsRules()`, unless
     * they were made for another data type.
     *
     * @param  class-string<DataTypeInterface>  $dataType
     * @return array<string, mixed>
     */
    public function dataTypeSettingsFor(string $dataType): array
    {
        return self::settingsForDataType($this->dataTypeSettings, $dataType);
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
                function (string $attribute, mixed $value, Closure $fail, Validator $validator) use ($resolveHost) {
                    // a non-string value is reported by the `string` rule
                    if (! is_string($value)) {
                        return;
                    }

                    $dataType = $validator->getData()['settings']['dataType'] ?? null;
                    $error = self::sourceError($value, $resolveHost) ?? self::dataTypeError($value, is_string($dataType) ? $dataType : null);

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
     * Returns the names of the settings the importer can be configured with, each applied through the setter of the same name.
     *
     * @return list<string>
     */
    protected static function settingNames(): array
    {
        return ['map', 'matchCriteria', 'clearableItems', 'defaultValues', 'templates', 'dataType', 'dataTypeSettings'];
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
            'settings.defaultValues' => ['nullable', 'array'],
            'settings.templates' => [
                'nullable',
                'array',
                fn (string $attribute, mixed $value, Closure $fail, Validator $validator) => self::validateTemplates($value, $validator),
            ],
            'settings.dataType' => ['nullable', 'string', 'in:'.implode(',', Import::getAllDataTypes())],
            'settings.dataTypeSettings' => [
                'nullable',
                'array',
                fn (string $attribute, mixed $value, Closure $fail, Validator $validator) => self::validateDataTypeSettings($value, $validator),
            ],
        ];
    }

    /**
     * Returns custom validation messages for the importer's settings rules.
     *
     * @return array<string, string>
     */
    public static function getSettingsMessages(): array
    {
        return [];
    }

    /**
     * Returns the importer's current state as a step, the shape the import plan edit screen holds.
     */
    public function toImportStep(): ImportStep
    {
        return new ImportStep(...$this->toArrayData());
    }

    /**
     * Builds the step data array validated by `getRules()`/`getSettingsRules()`, from the importer's current state.
     *
     * @return array{uid: string|null, type: class-string<static>, source: string|null, transformer: string|null, batchSize: int|null, enabled: bool, settings: array<string, mixed>}
     */
    public function toArrayData(): array
    {
        return [
            'uid' => $this->uid,
            'type' => static::class,
            'source' => $this->source,
            'transformer' => $this->transformerAsString(),
            'batchSize' => $this->batchSize,
            'enabled' => $this->enabled,
            'settings' => [
                'map' => $this->map,
                'matchCriteria' => $this->matchCriteria,
                'clearableItems' => $this->clearableItems,
                'defaultValues' => $this->defaultValues,
                'templates' => $this->templates,
                'dataType' => $this->dataType,
                'dataTypeSettings' => $this->dataTypeSettings,
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
        ValidatorFacade::make($this->toArrayData(), static::getRules(), static::getSettingsMessages())->validate();
    }

    /**
     * Validates only the importer's execution settings.
     *
     * @throws ValidationException
     */
    public function validateSettings(): void
    {
        ValidatorFacade::make($this->toArrayData(), static::getSettingsRules(), static::getSettingsMessages())->validate();
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
     * Returns an error message if a local source that `sourceError()` accepts can't be read as a single data type,
     * or null if it can. A remote source's data type is only checked once it's downloaded.
     *
     * @param  string|null  $source  The source alias, path or URL to check.
     * @param  class-string<DataTypeInterface>|null  $dataType  The data type chosen to read the source as, if any.
     */
    public static function dataTypeError(?string $source, ?string $dataType): ?string
    {
        if (self::sourceError($source, resolveHost: false) !== null || self::isRemoteSource($source)) {
            return null;
        }

        return Import::getDataTypeError((string) self::resolvedSourcePath($source), $dataType);
    }

    /**
     * Returns the data types a source could be read as: for a local file, those its extension and contents allow;
     * for a URL, those its extension allows. Empty if the source can't be used, and null for a URL whose extension
     * no data type claims, as its data type is only known from the response.
     *
     * @param  string|null  $source  The source alias, path or URL.
     * @return list<class-string<DataTypeInterface>>|null
     */
    public static function sourceDataTypes(?string $source): ?array
    {
        if (self::sourceError($source, resolveHost: false) !== null) {
            return [];
        }

        $sourcePath = (string) self::resolvedSourcePath($source);

        if (! Url::isValidUrl($sourcePath)) {
            return Import::getDataTypeCandidates($sourcePath);
        }

        return Import::getDataTypesForExtension((string) parse_url($sourcePath, PHP_URL_PATH)) ?: null;
    }

    /**
     * Returns the data type a source is definitely read as, or null while that's still open: when more than one data
     * type could read it and none of them is chosen, or a URL's data type is left to its response.
     *
     * @param  string|null  $source  The source alias, path or URL.
     * @param  class-string<DataTypeInterface>|null  $dataType  The data type chosen to read the source as, if any.
     * @return class-string<DataTypeInterface>|null
     */
    public static function resolvedDataType(?string $source, ?string $dataType): ?string
    {
        $dataTypes = self::sourceDataTypes($source);

        // a URL whose extension no data type claims is only definitely read as a chosen data type
        if ($dataTypes === null) {
            return in_array($dataType, Import::getAllDataTypes(), true) ? $dataType : null;
        }

        // the source can't be used
        if ($dataTypes === []) {
            return null;
        }

        $sourcePath = (string) self::resolvedSourcePath($source);
        $claimingTypes = Import::getDataTypesForExtension(Url::isValidUrl($sourcePath) ? (string) parse_url($sourcePath, PHP_URL_PATH) : $sourcePath);

        // a choice only counts when more than one data type claims the extension, and then only if the contents match it
        if ($dataType !== null && count($claimingTypes) > 1) {
            return in_array($dataType, $dataTypes, true) ? $dataType : null;
        }

        return count($dataTypes) === 1 ? $dataTypes[0] : null;
    }

    /**
     * Returns the settings in `$settings` that a data type reads a source with: those named by its
     * `getSettingsRules()`, unless they were made for another data type.
     *
     * @param  array<string, mixed>  $settings
     * @param  class-string<DataTypeInterface>  $dataType
     * @return array<string, mixed>
     */
    private static function settingsForDataType(array $settings, string $dataType): array
    {
        if (($settings['type'] ?? $dataType) !== $dataType) {
            return [];
        }

        return Arr::only($settings, array_keys($dataType::getSettingsRules()));
    }

    /**
     * Validates a step's data type settings against the rules of the data type its source is definitely read as,
     * reporting each error under the setting's own key. Nothing is checked while the data type is still open.
     */
    private static function validateDataTypeSettings(mixed $value, Validator $validator): void
    {
        $data = $validator->getData();
        $dataType = $data['settings']['dataType'] ?? null;
        $source = $data['source'] ?? null;

        if (! is_array($value) || ! is_string($source) || ($dataType !== null && ! is_string($dataType))) {
            return;
        }

        $resolvedDataType = self::resolvedDataType($source, $dataType);

        if ($resolvedDataType === null) {
            return;
        }

        $settingsValidator = ValidatorFacade::make(self::settingsForDataType($value, $resolvedDataType), $resolvedDataType::getSettingsRules());

        foreach ($settingsValidator->errors()->messages() as $setting => $messages) {
            foreach ($messages as $message) {
                $validator->errors()->add("settings.dataTypeSettings.$setting", $message);
            }
        }
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

        // a URL whose extension no data type claims is read as the data type chosen for it, if there is one
        $dataType = Import::getDataTypeFromExtension((string) parse_url($url, PHP_URL_PATH)) === null && in_array($this->dataType, Import::getAllDataTypes(), true)
            ? strtolower($this->dataType::extensions()[0])
            : self::getDataTypeFromContentType($response->header('Content-Type'), $url);

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
            'fileTypes' => implode(', ', Import::getDataTypeExtensions()),
        ]);
    }

    /**
     * Returns an error message if a local source's contents don't match any data type that claims its extension,
     * or null if they do (or if the extension isn't a data type, which `getExtensionError()` reports).
     */
    private static function getContentError(string $sourcePath, ?string $url = null): ?string
    {
        $dataType = Import::getDataTypeFromExtension($sourcePath);

        if ($dataType === null) {
            return null;
        }

        $candidates = Import::getDataTypeCandidates($sourcePath);
        $contentsMatch = false;

        if ($candidates !== []) {
            $contentsMatch = ValidatorFacade::make(
                ['source' => new File($sourcePath)],
                ['source' => ['mimes:'.implode(',', array_merge(...array_map(fn (string $type) => $type::extensions(), $candidates)))]],
            )->passes();

            // plain text has no signature to detect, and JSON is only detected within the first few MB,
            // so plain text is checked against the data types themselves
            if (! $contentsMatch && new File($sourcePath)->getMimeType() === 'text/plain') {
                $sample = Import::getSample($sourcePath);
                $contentsMatch = array_any($candidates, fn (string $type) => $type::matches($sample));
            }
        }

        if (! $contentsMatch) {
            return t('The contents of “{source}” don’t match its type ({dataType}).', [
                'source' => $url ?? $sourcePath,
                'dataType' => $dataType,
            ]);
        }

        return null;
    }

    /**
     * Returns the data type (extension) of a remote source, based on the URL path's extension, falling back to the
     * response's content type, or null if neither is one of the available data types.
     *
     * The URL's extension comes first, as generic content types (such as `text/plain`) map to extensions that
     * other data types claim.
     */
    private static function getDataTypeFromContentType(string $contentType, string $url): ?string
    {
        $dataType = Import::getDataTypeFromExtension((string) parse_url($url, PHP_URL_PATH));

        if ($dataType !== null) {
            return $dataType;
        }

        $extensions = Import::getDataTypeExtensions();
        $mimeType = strtolower(trim(explode(';', $contentType)[0]));

        if ($mimeType !== '') {
            foreach (MimeTypes::getDefault()->getExtensions($mimeType) as $extension) {
                if (in_array($extension, $extensions, true)) {
                    return $extension;
                }
            }
        }

        return null;
    }

    /**
     * Returns the validator used to check import URLs.
     */
    public static function urlValidator(?callable $resolver = null): UrlValidator
    {
        return new UrlValidator($resolver, [
            // only the hosts listed in the allowedPrivateHosts config setting can resolve to private IP addresses
            'allowedPrivateHosts' => Cms::config()->allowedPrivateHosts,
        ]);
    }

    /**
     * Returns whether a local file may be read as an import source: not a dotfile, and not in a system or
     * sensitive directory, before or after its symlinks are resolved.
     */
    public static function isFilepathAllowed(string $filePath): bool
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
    public static function validateTransformer(mixed $value, string $attribute, Closure $fail, Validator $validator): void
    {
        // empty is fine (the importer's default transformer is used)
        if (! empty($value) && self::normalizeTransformer($value) === null) {
            $fail($attribute, t('Transformer has to be empty or a valid transformer class.'));
        }
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
     * Checks that every `$map` leaf set to `ImportHelper::TEMPLATE_RULE` has a template, and that each of those
     * templates compiles, adding any errors under the template's own path.
     */
    protected static function validateTemplates(mixed $templates, Validator $validator): void
    {
        $map = ImportHelper::decodeRecursive($validator->getData()['settings']['map'] ?? []);

        if (! is_array($map)) {
            return;
        }

        $templates = is_array($templates) ? $templates : [];

        foreach (Arr::dot($map) as $path => $rule) {
            if ($rule !== ImportHelper::TEMPLATE_RULE) {
                continue;
            }

            $attribute = "settings.templates.$path";
            $template = Arr::get($templates, (string) $path);

            if (! is_string($template) || trim($template) === '') {
                $validator->errors()->add($attribute, t('{attribute} cannot be blank.', ['attribute' => t('Template')]));

                continue;
            }

            try {
                renderSandboxedObjectTemplate($template, []);
            } catch (SyntaxError|SecurityError $e) {
                $validator->errors()->add($attribute, t('The template couldn’t be parsed: {error}', ['error' => $e->getRawMessage()]));
            } catch (RuntimeError) {
                // rendered without the incoming data, so errors that depend on it can only show up at import time
            }
        }
    }

    /**
     * Prepares the importer's settings for an import run.
     *
     * Mapping trees can use uids instead of handles (see `resolveMappingKeys()`), and the import needs
     * handles. Call this right before importing, but not when the settings will be shown or saved again.
     */
    public function prepareForImport(): void
    {
        $this->map = $this->resolveMappingKeys($this->map);

        if ($this->matchCriteria !== null) {
            $this->matchCriteria = $this->resolveMappingKeys($this->matchCriteria);
        }

        if ($this->clearableItems !== null) {
            $this->clearableItems = $this->resolveMappingKeys($this->clearableItems);
        }

        if ($this->defaultValues !== null) {
            $this->defaultValues = $this->resolveMappingKeys($this->defaultValues);
        }

        if ($this->templates !== null) {
            $this->templates = $this->resolveMappingKeys($this->templates);
        }
    }

    /**
     * Replaces any uid keys in a mapping tree with the current handles.
     * By default, the tree is returned unchanged.
     *
     * @param  array<mixed>  $tree
     * @return array<mixed>
     */
    public function resolveMappingKeys(array $tree): array
    {
        return $tree;
    }

    /**
     * The reverse of {@see resolveMappingKeys()}: replaces any handle keys in a mapping tree with the uids
     * the mapping screen keys them by. By default, the tree is returned unchanged.
     *
     * @param  array<mixed>  $tree
     * @return array<mixed>
     */
    public function uidMappingKeys(array $tree): array
    {
        return $tree;
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
     * Returns each destination column's label, handle and icon, keyed by its key in `$map`, and by
     * its handle, since a map can hold either. A compound column's subfields read as “Heading › Label”.
     *
     * A container field's nested columns are keyed by their whole path in `$map`, joined with dots,
     * both as uids and as handles, and read as “Entry type › Label”.
     *
     * @return array<string, array{label: string, handle: string, icon: string|null}>
     */
    public function getDestinationLabels(): array
    {
        $labels = [];

        foreach (self::flattenCompoundColumns($this->getDestinationCols()) as [$col, $label]) {
            $entry = ['label' => $label, 'handle' => $col->handle, 'icon' => $col->icon];
            $labels[$col->prefixedHandleAsArray[0]] ??= $entry;
            $labels[$col->handle] ??= $entry;

            if ($col->isContainer) {
                self::addNestedDestinationLabels($labels, $col, [$col->handle], []);
            }
        }

        return $labels;
    }

    /**
     * Adds the labels of a container column's nested columns, and of theirs in turn.
     *
     * @param  array<string, array{label: string, handle: string, icon: string|null}>  $labels
     * @param  list<string>  $handlePath  The container's path in `$map`, as handles.
     * @param  list<string>  $seen  The containers' field uids along the path, so a field nested in itself stops.
     */
    private static function addNestedDestinationLabels(array &$labels, MappingColumn $container, array $handlePath, array $seen): void
    {
        if ($container->fieldUid === null || in_array($container->fieldUid, $seen, true) || count($seen) >= 5) {
            return;
        }

        $field = Fields::getFieldByUid($container->fieldUid);

        if (! $field instanceof ImportableElementContainerFieldInterface) {
            return;
        }

        $seen[] = $container->fieldUid;
        $depth = count($container->prefixedHandleAsArray);

        foreach ($field->getFieldLayoutProviders() as $provider) {
            $providerName = $provider instanceof Chippable ? $provider->getUiLabel() : $provider->getHandle();
            $cols = ImportHelper::getDestinationColsForFieldLayout($provider->getFieldLayout(), $field, $provider, $container->prefixedHandle);

            foreach (self::flattenCompoundColumns($cols) as [$col, $label]) {
                // the column's path below the container: its provider, maybe `fields`, then its own key
                $below = array_slice($col->prefixedHandleAsArray, $depth);
                $colHandlePath = [...$handlePath, (string) $provider->getHandle(), ...array_slice($below, 1, -1), $col->handle];
                $entry = ['label' => "$providerName › $label", 'handle' => $col->handle, 'icon' => $col->icon];

                $labels[implode('.', $col->prefixedHandleAsArray)] ??= $entry;
                $labels[implode('.', $colHandlePath)] ??= $entry;

                if ($col->isContainer) {
                    self::addNestedDestinationLabels($labels, $col, $colHandlePath, $seen);
                }
            }
        }
    }

    /**
     * Each column with its label, compound columns' subfields in their place.
     *
     * @param  list<MappingColumn|CompoundMappingColumn>  $cols
     * @return list<array{MappingColumn, string}>
     */
    private static function flattenCompoundColumns(array $cols): array
    {
        $flat = [];

        foreach ($cols as $entry) {
            if (! $entry instanceof CompoundMappingColumn) {
                $flat[] = [$entry, $entry->label];

                continue;
            }

            foreach ($entry->subfields as $col) {
                $flat[] = [$col, count($col->prefixedHandleAsArray) > 1
                    ? (string) $entry->heading
                    : ($entry->heading ? "$entry->heading › $col->label" : $col->label)];
            }
        }

        return $flat;
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
        return $this->transformer ? $this->transformer::class : null;
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
        return $this->transformerAsString() === static::getDefaultTransformer();
    }
}
