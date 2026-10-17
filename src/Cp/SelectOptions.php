<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use Closure;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Route\CurrentElement;
use CraftCms\Cms\Route\ElementRoute;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Security;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Support\Path;
use CraftCms\Cms\Translation\Locale;
use CraftCms\Cms\Ui\Controls\Concerns\HasTextExpander;
use CraftCms\Cms\View\Contracts\ProvidesObjectTemplateSuggestions;
use CraftCms\Cms\View\TemplateMode;
use DateTimeInterface;
use DateTimeZone;
use FilesystemIterator;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;
use SplFileInfo;

use function CraftCms\Cms\t;

/**
 * @phpstan-import-type TextExpanderTrigger from HasTextExpander
 *
 * @phpstan-type SuggestionOption array{label: string, value: string, data: array{hint: mixed, keywords?: string|null}}
 *
 * @since 6.0.0
 */
class SelectOptions
{
    /**
     * Returns the available environment variable and alias suggestions for
     * inputs that support them.
     *
     * @param  bool  $includeAliases  Whether aliases should be included in the list
     *                                (only enable this if the setting defines a URL or file path)
     * @param  callable|null  $filter  A function that returns whether a given value should be included
     *
     * @phpstan-param callable(scalar):bool|null $filter
     *
     * @phpstan-return array{0: array{type: 'optgroup', label: string, options: list<SuggestionOption>}, 1?: array{type: 'optgroup', label: string, options: list<SuggestionOption>}}
     */
    public static function getEnvSuggestions(bool $includeAliases = false, ?callable $filter = null)
    {
        $suggestions = [];

        $envSuggestions = [];
        foreach (array_keys($_SERVER) as $var) {
            if (
                is_string($var) &&
                ! str_starts_with($var, 'HTTP_') &&
                is_scalar($env = Env::get($var)) &&
                (! $filter || $filter($env))
            ) {
                $hint = Security::redactIfSensitive($var, Aliases::get((string) $env, false));
                $envSuggestions[] = [
                    'label' => '$'.$var,
                    'value' => '$'.$var,
                    'data' => [
                        'hint' => $hint,
                        // The hint is a variable's value, and Craft 5's autosuggest
                        // matched against it — so you can find `$PRIMARY_SITE_URL`
                        // by typing part of the URL it holds.
                        'keywords' => $hint,
                    ],
                ];
            }
        }

        $suggestions[] = [
            'type' => 'optgroup',
            'label' => t('Environment Variables'),
            'options' => array_values(Arr::sort($envSuggestions, 'label')),
        ];

        if ($includeAliases) {
            $aliasSuggestions = [];
            foreach (Aliases::getAll() as $alias => $path) {
                if (! $filter || $filter($path)) {
                    $aliasSuggestions[] = [
                        'label' => $alias,
                        'value' => $alias,
                        'data' => [
                            'hint' => $path,
                            'keywords' => is_string($path) ? $path : null,
                        ],
                    ];
                }
            }
            $suggestions[] = [
                'type' => 'optgroup',
                'label' => t('Aliases'),
                'options' => array_values(Arr::sort($aliasSuggestions, 'label')),
            ];
        }

        return $suggestions;
    }

    /** @return list<TextExpanderTrigger> */
    public static function getEnvTextExpanderTriggers(bool $includeAliases = false, ?callable $filter = null): array
    {
        $suggestions = self::getEnvSuggestions($includeAliases, $filter);
        $environmentOptions = $suggestions[0]['options'];
        $triggers = [];

        if (! empty($environmentOptions)) {
            $triggers[] = [
                'trigger' => '$',
                'boundary' => 'start',
                'label' => t('Environment Variables'),
                'options' => $environmentOptions,
            ];
            $triggers[] = [
                'trigger' => '$',
                'boundary' => 'anywhere',
                'label' => t('Environment Variables'),
                'options' => array_map(function (array $option): array {
                    $name = substr($option['value'], 1);

                    return [
                        ...$option,
                        'value' => sprintf('${%s}', $name),
                    ];
                }, $environmentOptions),
            ];
        }

        $aliasOptions = $suggestions[1]['options'] ?? [];
        if (! empty($aliasOptions)) {
            $triggers[] = [
                'trigger' => '@',
                'boundary' => 'start',
                'label' => t('Aliases'),
                'options' => $aliasOptions,
            ];
        }

        return $triggers;
    }

    /**
     * @param  class-string<ProvidesObjectTemplateSuggestions>  $objectType
     * @param  list<FieldLayout>  $fieldLayouts
     * @param  array<string, string>  $additionalProperties
     * @return list<TextExpanderTrigger>
     */
    public static function getObjectTemplateTextExpanderTriggers(
        string $objectType = Element::class,
        array $fieldLayouts = [],
        array $additionalProperties = [],
    ): array {
        $properties = $objectType::objectTemplateSuggestions();

        foreach ($fieldLayouts as $fieldLayout) {
            $properties = array_merge($properties, $fieldLayout->objectTemplateSuggestions());
        }

        $properties = array_merge($properties, $additionalProperties);
        $options = Collection::make($properties)
            ->map(function (string $label, string $property): array {
                $value = sprintf('{%s}', $property);

                return [
                    'label' => $label,
                    'value' => $value,
                    'keywords' => [$property],
                    'data' => ['hint' => $value],
                ];
            })
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        return [[
            'trigger' => '{',
            'boundary' => 'anywhere',
            'label' => t('Object Template Variables'),
            'options' => $options,
        ]];
    }

    public static function getObjectTemplateTip(): string
    {
        return t('Type <kbd>{</kbd> to choose an object template variable.');
    }

    /**
     * Returns environment variable options for a select input.
     */
    /**
     * @param  list<scalar>|null  $allowedValues
     * @return list<array<string, mixed>>
     */
    public static function getEnvOptions(?array $allowedValues = null): array
    {
        if ($allowedValues !== null) {
            if (empty($allowedValues)) {
                return [];
            }

            $allowedValues = array_flip(array_filter($allowedValues));
        }

        $options = [];

        foreach (array_keys($_SERVER) as $var) {
            if (
                is_string($var) &&
                ! str_starts_with($var, 'HTTP_') &&
                is_string($value = Env::get($var)) &&
                ($allowedValues === null || isset($allowedValues[$value]))
            ) {
                $data = [];
                if ($value !== '') {
                    $data['hint'] = Security::redactIfSensitive($var, Aliases::get($value, false));
                }

                $options[] = array_filter([
                    'label' => "$$var",
                    'value' => "$$var",
                    'data' => ! empty($data) ? $data : null,
                ]);
            }
        }

        return self::formatEnvOptions($options);
    }

    /**
     * Returns environment variable options for a boolean menu.
     */
    /**
     * @return list<array{
     *     type: 'optgroup',
     *     label: string,
     *     options: Collection<int, array{label: string, value: string, data: array{boolean: string}}>,
     * }>
     */
    public static function getBooleanEnvOptions(): array
    {
        $options = [];

        foreach (array_keys($_SERVER) as $var) {
            if (! is_string($var)) {
                continue;
            }
            $value = Env::get($var);
            if ($value === null) {
                continue;
            }
            if ($value === '') {
                continue;
            }
            $booleanValue = is_bool($value) ? $value : filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            if ($booleanValue !== null) {
                $options[] = [
                    'label' => "$$var",
                    'value' => "$$var",
                    'data' => [
                        'boolean' => $booleanValue ? '1' : '0',
                    ],
                ];
            }
        }

        return [
            [
                'type' => 'optgroup',
                'label' => t('Environment Variables'),
                'options' => collect($options)->sortBy('value')->values(),
            ],
        ];
    }

    /**
     * Returns environment variable options for a language menu.
     *
     * @param  bool  $appOnly  Whether to limit the env options to those that match available app locales
     */
    /** @return list<array<string, mixed>> */
    public static function getLanguageEnvOptions(bool $appOnly = false): array
    {
        $options = [];
        if ($appOnly) {
            $allLanguages = I18N::getAppLocales()->map(fn (Locale $locale) => $locale->id);
        } else {
            $allLanguages = I18N::getAllLocales()->map(fn (Locale $locale) => $locale->id);
        }

        foreach (array_keys($_SERVER) as $var) {
            if (! is_string($var)) {
                continue;
            }
            $value = Env::get($var);
            if ($value === null) {
                continue;
            }
            if ($value === '') {
                continue;
            }

            $languageValue = null;
            if ($allLanguages->containsStrict($value)) {
                $languageValue = $value;
            }

            if ($languageValue !== null) {
                $options[] = [
                    'label' => "$$var",
                    'value' => "$$var",
                    'data' => [
                        'hint' => $languageValue,
                    ],
                ];
            }
        }

        return self::formatEnvOptions($options);
    }

    /**
     * Returns all known language options for a language input.
     *
     * @param  bool  $showLocaleIds  Whether to show the hint as locale id; e.g. en, en-GB
     * @param  bool  $showLocalizedNames  Whether to show the hint as localizes names; e.g. English, English (United Kingdom)
     * @param  bool  $appLocales  Whether to limit the returned locales to just app locales (cp translation options) or show them all
     */
    /** @return list<array<string, mixed>> */
    public static function getLanguageOptions(
        bool $showLocaleIds = false,
        bool $showLocalizedNames = false,
        bool $appLocales = false,
    ): array {
        $options = [];

        if ($appLocales) {
            $allLocales = I18N::getAppLocales();
        } else {
            $allLocales = I18N::getAllLocales();
        }

        $allLocales = $allLocales->sortBy(fn (Locale $locale) => $locale->getDisplayName(app()->getLocale()));

        foreach ($allLocales as $locale) {
            $label = $locale->getDisplayName(app()->getLocale());
            // The locale's name in its own language, whatever language the CP is in
            $name = $locale->getDisplayName();
            $option = [
                'label' => $label,
                'value' => $locale->id,
                'data' => [
                    'keywords' => $name,
                ],
            ];

            $hints = [];
            if ($showLocaleIds) {
                $hints[] = $locale->id;
            }
            if ($showLocalizedNames && $name !== $label) {
                $hints[] = $name;
                $option['data']['hintLang'] = $locale->id;
            }
            if (! empty($hints)) {
                $option['data']['hint'] = implode(', ', $hints);
            }

            $options[] = $option;
        }

        return $options;
    }

    /**
     * Returns all options for a Laravel filesystem disk input.
     */
    /** @return list<array{label: string, value: string}> */
    public static function getDiskOptions(): array
    {
        return Collection::make(Arr::wrap(config('filesystems.disks', [])))
            ->keys()
            ->filter(function (mixed $diskName): bool {
                if (! is_string($diskName)) {
                    return false;
                }

                return ! in_array($diskName, Volume::INTERNAL_DISK_NAMES, true);
            })
            ->map(fn (string $diskName) => [
                'label' => $diskName,
                'value' => $diskName,
            ])
            ->sortBy(fn (array $option) => $option['label'])
            ->values()
            ->all();
    }

    /**
     * Returns all options for a volume input.
     */
    /** @return array<int|string, array{label: string, value: int}> */
    public static function getVolumeOptions(): array
    {
        return Volumes::getAllVolumes()
            ->map(fn (Volume $volume) => [
                'label' => $volume->name,
                'value' => $volume->id,
            ])
            ->sortBy('label')
            ->all();
    }

    /** @return list<array<string, mixed>> */
    public static function getTimeZoneOptions(?DateTimeInterface $offsetDate = null): array
    {
        // Assemble the timezone options array (Technique adapted from http://stackoverflow.com/a/7022536/1688568)
        $options = [];

        $offsetDate = $offsetDate ? Date::instance($offsetDate)->setTimezone('UTC') : now('UTC');
        $offsets = [];
        $timezoneIds = [];

        foreach (DateTimeZone::listIdentifiers() as $timezoneId) {
            $timezone = new DateTimeZone($timezoneId);
            $abbr = DateTimeHelper::timeZoneAbbreviation($timezone, $offsetDate);

            $offset = round($timezone->getOffset($offsetDate) / 60);

            if ($offset) {
                $hour = floor($offset / 60);
                $minutes = floor(abs($offset) % 60);
                $format = sprintf('%+03d:%02u', $hour, $minutes);
            } else {
                $format = '';
            }

            $label = "(GMT$format)";
            if (preg_match('/^[A-Z]+$/', $abbr)) {
                $label .= " $abbr";
            }

            $data = [];

            if ($timezoneId !== 'UTC') {
                [, $city] = explode('/', $timezoneId, 2);
                // Cleanup, e.g. North_Dakota/New_Salem => New Salem, North Dakota
                $data['hint'] = str_replace('_', ' ', implode(', ', array_reverse(explode('/', $city))));
            }

            $offsets[] = $offset;
            $timezoneIds[] = $timezoneId;
            $options[] = array_filter([
                'value' => $timezoneId,
                'label' => $label,
                'data' => ! empty($data) ? $data : null,
            ]);
        }

        array_multisort($offsets, SORT_ASC, SORT_NUMERIC, $timezoneIds, $options);

        return $options;
    }

    /**
     * @param  list<array<string, mixed>>  $options
     * @return list<array<string, mixed>>
     */
    public static function formatEnvOptions(array $options): array
    {
        return [
            [
                'type' => 'optgroup',
                'label' => t('Environment Variables'),
                'options' => Collection::make($options)->sortBy('value')->values()->all(),
            ],
        ];
    }

    /**
     * Returns the available template path suggestions for template inputs.
     *
     * @return array[]
     *
     * @phpstan-return array{array{label: string, type: 'optgroup', options: list<array{label: string, value: string, data: array{hint: string|null}}>}}
     *
     * @since 6.0.0
     */
    public static function getTemplateSuggestions(): array
    {
        // Get all the template files sorted by path length
        $roots = Arr::merge([
            '' => [app(Path::class)->siteTemplates()],
        ], TemplateMode::Site->templateRoots());

        $suggestions = [];
        $templates = [];
        $sites = [];

        foreach (Sites::getAllSites() as $site) {
            $sites[$site->handle] = t($site->getName(), category: 'site');
        }

        foreach ($roots as $root => $basePaths) {
            foreach ($basePaths as $basePath) {
                if (! is_dir($basePath)) {
                    continue;
                }

                $directory = new RecursiveDirectoryIterator($basePath);

                $filter = new RecursiveCallbackFilterIterator($directory, function ($current) {
                    // Skip hidden files and directories, as well as node_modules/ folders
                    if ($current->getFilename()[0] === '.' || $current->getFilename() === 'node_modules') {
                        return false;
                    }

                    return true;
                });

                $iterator = new RecursiveIteratorIterator($filter);
                /** @var SplFileInfo[] $files */
                $files = [];
                $pathLengths = [];

                foreach ($iterator as $file) {
                    /** @var SplFileInfo $file */
                    if (! $file->isDir() && $file->getFilename()[0] !== '.') {
                        $files[] = $file;
                        $pathLengths[] = strlen($file->getRealPath());
                    }
                }

                array_multisort($pathLengths, SORT_NUMERIC, $files);

                $basePathLength = strlen((string) $basePath);

                foreach ($files as $file) {
                    $template = substr($file->getRealPath(), $basePathLength + 1);
                    $hint = null;

                    // Is it in a site template directory?
                    foreach ($sites as $handle => $name) {
                        if (str_starts_with($template, $handle.DIRECTORY_SEPARATOR)) {
                            $hint = $name;
                            $template = substr($template, strlen((string) $handle) + 1);
                            break;
                        }
                    }

                    // Prepend the template root path
                    if ($root !== '') {
                        $template = sprintf('%s/%s', $root, $template);
                    }

                    // Avoid listing the same template path twice (considering localized templates)
                    if (isset($templates[$template])) {
                        continue;
                    }

                    $templates[$template] = true;
                    $suggestions[] = [
                        'label' => $template,
                        'value' => $template,
                        'data' => [
                            'hint' => $hint,
                            'keywords' => $hint,
                        ],
                    ];
                }
            }
        }

        return [
            [
                'label' => t('Templates'),
                'type' => 'optgroup',
                'options' => array_values(Arr::sort($suggestions, 'label')),
            ],
        ];
    }

    /**
     * Returns the application’s named routes and controller actions, for element route autosuggestions.
     *
     * Controller actions that are already registered as routes are excluded, since element routes can
     * only dispatch to them when the registered route matches the element’s URI.
     *
     * @param  class-string<ElementInterface>|null  $elementType  Excludes controller actions whose element
     *                                                            parameter (`#[CurrentElement]`, or else `$element`) can’t accept this element type.
     *
     * @phpstan-return list<array{label: string, type: 'optgroup', options: list<array{label: string, value: string, data: array{hint: string|null, keywords: string|null}}>}>
     */
    public static function getRouteSuggestions(?string $elementType = null): array
    {
        $appNamespace = app()->getNamespace();
        $routes = app(Router::class)->getRoutes()->getRoutes();
        $suggestions = [];
        $registeredActions = [];

        foreach ($routes as $route) {
            $action = ltrim($route->getActionName(), '\\');
            $registeredActions[strtolower(str_contains($action, '@') ? $action : "$action@__invoke")] = true;
            $name = $route->getName();

            if (
                $name === null ||
                ! in_array('GET', $route->methods(), true) ||
                str_starts_with($name, 'craft.') ||
                ! self::isAppRoute($route, $action, $appNamespace) ||
                ! ElementRoute::isValid($name) ||
                ($elementType !== null && ! self::routeAcceptsElementType($route, $action, $elementType))
            ) {
                continue;
            }

            $uri = '/'.ltrim($route->uri(), '/');
            $suggestions[$name] = [
                'label' => $name,
                'value' => $name,
                'data' => [
                    'hint' => $uri,
                    'keywords' => $uri,
                ],
            ];
        }

        foreach (self::controllerActions($appNamespace) as $action) {
            $key = strtolower(str_contains($action, '@') ? $action : "$action@__invoke");
            if (isset($registeredActions[$key]) || ($elementType !== null && ! self::actionAcceptsElementType($action, $elementType))) {
                continue;
            }

            $suggestions[$action] = [
                'label' => $action,
                'value' => $action,
                'data' => [
                    'hint' => null,
                    'keywords' => null,
                ],
            ];
        }

        if ($suggestions === []) {
            return [];
        }

        return [
            [
                'label' => t('Routes'),
                'type' => 'optgroup',
                'options' => array_values(Arr::sort($suggestions, 'label')),
            ],
        ];
    }

    /**
     * Returns whether a named route’s closure or controller action can accept the given element type.
     *
     * @param  class-string<ElementInterface>  $elementType
     */
    private static function routeAcceptsElementType(Route $route, string $action, string $elementType): bool
    {
        if ($action !== 'Closure') {
            return self::actionAcceptsElementType($action, $elementType);
        }

        $closure = $route->getAction('uses');

        return ! $closure instanceof Closure || self::functionAcceptsElementType(new ReflectionFunction($closure), $elementType);
    }

    /**
     * Returns whether a controller action can accept the given element type.
     *
     * @param  class-string<ElementInterface>  $elementType
     */
    private static function actionAcceptsElementType(string $action, string $elementType): bool
    {
        [$class, $method] = explode('@', str_contains($action, '@') ? $action : "$action@__invoke", 2);

        return ! method_exists($class, $method) || self::functionAcceptsElementType(new ReflectionMethod($class, $method), $elementType);
    }

    /**
     * Returns whether a route handler’s element parameter can accept the given element type.
     *
     * The element parameter is the one with a `#[CurrentElement]` attribute, or else one named `$element`.
     * Handlers without an element parameter accept any element type.
     *
     * @param  class-string<ElementInterface>  $elementType
     */
    private static function functionAcceptsElementType(ReflectionFunctionAbstract $function, string $elementType): bool
    {
        $parameters = $function->getParameters();
        $elementParameters = array_filter($parameters, fn (ReflectionParameter $parameter): bool => $parameter->getAttributes(CurrentElement::class) !== [])
            ?: array_filter($parameters, fn (ReflectionParameter $parameter): bool => $parameter->getName() === 'element');

        return array_all($elementParameters, fn (ReflectionParameter $parameter): bool => self::typeAccepts($parameter->getType(), $elementType));
    }

    /**
     * Returns whether a parameter type accepts an instance of the given class.
     */
    private static function typeAccepts(?ReflectionType $type, string $class): bool
    {
        return match (true) {
            $type === null => true,
            $type instanceof ReflectionNamedType => $type->isBuiltin()
                ? in_array($type->getName(), ['mixed', 'object'], true)
                : is_a($class, $type->getName(), true),
            $type instanceof ReflectionUnionType => array_any($type->getTypes(), fn (ReflectionType $type): bool => self::typeAccepts($type, $class)),
            $type instanceof ReflectionIntersectionType => array_all($type->getTypes(), fn (ReflectionType $type): bool => self::typeAccepts($type, $class)),
            default => true,
        };
    }

    /**
     * Returns whether a route is defined by the application, rather than Craft, a plugin, or a package.
     */
    private static function isAppRoute(Route $route, string $action, string $appNamespace): bool
    {
        if ($action !== 'Closure') {
            return str_starts_with($action, $appNamespace);
        }

        $closure = $route->getAction('uses');

        if (! $closure instanceof Closure) {
            return false;
        }

        $file = new ReflectionFunction($closure)->getFileName();

        return $file !== false && ! str_contains($file, DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR);
    }

    /**
     * Returns the public actions of the application’s controllers, as element route destinations.
     *
     * @return list<string>
     */
    private static function controllerActions(string $appNamespace): array
    {
        $directory = app_path('Http/Controllers');

        if (! is_dir($directory)) {
            return [];
        }

        $actions = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            /** @var SplFileInfo $file */
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = substr($file->getPathname(), strlen($directory) + 1, -4);
            $class = $appNamespace.'Http\\Controllers\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

            if (! class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            if (! $reflection->isInstantiable()) {
                continue;
            }

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->isStatic() || $method->getDeclaringClass()->getName() !== $class) {
                    continue;
                }

                $name = $method->getName();

                if ($name === '__invoke') {
                    $actions[] = $class;
                } elseif (! str_starts_with($name, '__')) {
                    $actions[] = "$class@$name";
                }
            }
        }

        return $actions;
    }
}
