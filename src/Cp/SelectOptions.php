<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use Craft;
use craft\helpers\Assets;
use craft\services\Path;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Filesystem\Filesystems as FilesystemsService;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Filesystems;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Security;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Translation\Locale;
use CraftCms\Cms\View\TemplateMode;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Collection;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function CraftCms\Cms\t;

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
     * @phpstan-return array{0: array{type: 'optgroup', label: string, options: array}, 1?: array{type: 'optgroup', label: string, options: array}}
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
                $envSuggestions[] = [
                    'label' => '$'.$var,
                    'value' => '$'.$var,
                    'data' => [
                        'hint' => Security::redactIfSensitive($var, Aliases::get((string) $env, false)),
                    ],
                ];
            }
        }

        $suggestions[] = [
            'type' => 'optgroup',
            'label' => t('Environment Variables'),
            'options' => array_values(Arr::sort($envSuggestions, 'name')),
        ];

        if ($includeAliases) {
            $aliasSuggestions = [];
            foreach (Aliases::getAll() as $alias => $path) {
                // Don't ever suggest @web
                if ($alias === '@web') {
                    continue;
                }
                if (str_starts_with($alias, '@web/')) {
                    continue;
                }
                if (! $filter || $filter($path)) {
                    $aliasSuggestions[] = [
                        'label' => $alias,
                        'value' => $alias,
                        'data' => [
                            'hint' => $path,
                        ],
                    ];
                }
            }
            $suggestions[] = [
                'type' => 'optgroup',
                'label' => t('Aliases'),
                'options' => array_values(Arr::sort($aliasSuggestions, 'name')),
            ];
        }

        return $suggestions;
    }

    /**
     * Returns environment variable options for a select input.
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
    public static function getLanguageOptions(
        bool $showLocaleIds = false,
        bool $showLocalizedNames = false,
        bool $appLocales = false,
    ): array {
        $options = [];

        $languageId = I18N::getLocale()->getLanguageID();

        if ($appLocales) {
            $allLocales = I18N::getAppLocales();
        } else {
            $allLocales = I18N::getAllLocales();
        }

        $allLocales = $allLocales->sortBy(fn (Locale $locale) => $locale->getDisplayName());

        foreach ($allLocales as $locale) {
            $name = $locale->getLanguageID() !== $languageId ? $locale->getDisplayName() : '';
            $option = [
                'label' => $locale->getDisplayName(app()->getLocale()),
                'value' => $locale->id,
                'data' => [
                    'keywords' => $name,
                ],
            ];

            $hints = [];
            if ($showLocaleIds) {
                $hints[] = $locale->id;
            }
            if ($showLocalizedNames) {
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
     * Returns all options for a filesystem input.
     */
    public static function getFsOptions(): array
    {
        $craftFilesystemOptions = Filesystems::getAllFilesystems()
            ->reject(fn (FsInterface $fs): bool => Assets::isTempUploadFs($fs))
            ->map(fn (FsInterface $fs) => [
                'label' => t($fs->name, category: 'site'),
                'value' => $fs->handle,
            ]);

        $diskOptions = Collection::make(config('filesystems.disks', []))
            ->keys()
            ->filter(function (mixed $diskName): bool {
                if (! is_string($diskName)) {
                    return false;
                }

                if (in_array($diskName, FilesystemsService::INTERNAL_DISK_NAMES, true)) {
                    return false;
                }

                return ! str_starts_with($diskName, FilesystemsService::DISK_PREFIX);
            })
            ->map(fn (string $diskName) => [
                'label' => $diskName,
                'value' => "disk:$diskName",
            ]);

        return $craftFilesystemOptions
            ->concat($diskOptions)
            ->unique('value')
            ->sortBy(fn (array $option) => $option['label'])
            ->values()
            ->all();
    }

    /**
     * Returns all options for a volume input.
     */
    public static function getVolumeOptions(): array
    {
        return Collection::make(app(Craft::class)->getVolumes()->getAllVolumes())
            ->map(fn (Volume $volume) => [
                'label' => $volume->name,
                'value' => $volume->id,
            ])
            ->sortBy('label')
            ->all();
    }

    public static function getTimeZoneOptions(?DateTime $offsetDate = null): array
    {
        // Assemble the timezone options array (Technique adapted from http://stackoverflow.com/a/7022536/1688568)
        $options = [];

        $offsetDate ??= new DateTime;
        $offsetDate->setTimezone(new DateTimeZone('UTC'));
        $offsets = [];
        $timezoneIds = [];

        foreach (DateTimeZone::listIdentifiers() as $timezoneId) {
            $timezone = new DateTimeZone($timezoneId);
            $transition = $timezone->getTransitions($offsetDate->getTimestamp(), $offsetDate->getTimestamp());
            $abbr = $transition[0]['abbr'];

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
     * @phpstan-return array{label:string,data:array}[]
     *
     * @since 3.1.0
     */
    public static function getTemplateSuggestions(): array
    {
        // Get all the template files sorted by path length
        $roots = Arr::merge([
            '' => [app(Path::class)->getSiteTemplatesPath()],
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
                        ],
                    ];
                }
            }
        }

        return [
            [
                'label' => t('Templates'),
                'type' => 'optgroup',
                'options' => array_values(Arr::sort($suggestions, 'name')),
            ],
        ];
    }
}
