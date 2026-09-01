<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use Closure;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Utility\Utility;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Override;
use Symfony\Component\Filesystem\Path;

use function CraftCms\Cms\t;

/**
 * Provides cache-clearing actions and registers additional cache options.
 *
 * ```php
 * public function boot(): void
 * {
 *     ClearCaches::add('my-plugin', [
 *         'label' => 'My Plugin caches',
 *         'action' => MyCache::clear(...),
 *     ]);
 * }
 * ```
 */
/**
 * @phpstan-type CacheOption array{key: string, label: string, action: callable|string, info?: string, params?: array<array-key, mixed>}
 * @phpstan-type CacheOptionInput array{label: string, action: callable|string, info?: string, params?: array<array-key, mixed>}
 * @phpstan-type TagOption array{tag: string, label: string}
 */
class ClearCaches extends Utility
{
    /** @var array<string, CacheOptionInput|Closure(): CacheOptionInput> */
    private static array $additionalCacheOptions = [];

    /** @var array<string, string|Closure> */
    private static array $additionalTagOptions = [];

    /** @var (Closure(list<CacheOption>): list<CacheOption>)|null */
    private static ?Closure $optionTransformer = null;

    /** @var (Closure(list<TagOption>): list<TagOption>)|null */
    private static ?Closure $tagTransformer = null;

    #[Override]
    public static function displayName(): string
    {
        return t('Caches');
    }

    #[Override]
    public static function id(): string
    {
        return 'clear-caches';
    }

    #[Override]
    public static function icon(): string
    {
        return 'trash';
    }

    #[Override]
    public static function contentHtml(): string
    {
        $cacheOptions = [];
        $tagOptions = [];

        foreach (self::cacheOptions() as $cacheOption) {
            $cacheOptions[] = [
                'label' => $cacheOption['label'],
                'value' => $cacheOption['key'],
                'info' => isset($cacheOption['info']) ? Str::markdown($cacheOption['info']) : null,
            ];
        }

        foreach (self::tagOptions() as $tagOption) {
            $tagOptions[] = [
                'label' => $tagOption['label'],
                'value' => $tagOption['tag'],
            ];
        }

        $cacheOptions = Arr::sort($cacheOptions, 'label');

        return Html::tag('ClearCaches', attributes: [
            ':cacheOptions' => $cacheOptions,
            ':tagOptions' => $tagOptions,
        ]);
    }

    /**
     * Returns all cache options
     */
    /** @return list<CacheOption> */
    public static function cacheOptions(): array
    {
        $options = app()->runningUnitTests() ? [] : array_column(self::defaultCacheOptions(), null, 'key');

        foreach (self::$additionalCacheOptions as $key => $option) {
            $option = $option instanceof Closure ? app()->call($option) : $option;
            $action = $option['action'] ?? null;

            if (! isset($option['label']) ||
                ! is_string($option['label']) ||
                (! is_string($action) && ! is_callable($action))
            ) {
                throw new InvalidArgumentException("Invalid cache option [$key].");
            }

            $options[$key] = [...$option, 'key' => $key];
        }

        $options = array_values($options);

        if (isset(self::$optionTransformer)) {
            $options = app()->call(self::$optionTransformer, ['options' => $options]);
        }

        usort($options, fn (array $a, array $b) => $a['label'] <=> $b['label']);

        return $options;
    }

    /**
     * @param  CacheOptionInput|Closure(): CacheOptionInput  $option
     */
    public static function add(string $key, array|Closure $option): void
    {
        if ($key === '') {
            throw new InvalidArgumentException('Cache option keys cannot be empty.');
        }

        self::$additionalCacheOptions[$key] = $option;
    }

    public static function addTag(string $tag, string|Closure $label): void
    {
        if ($tag === '') {
            throw new InvalidArgumentException('Cache tags cannot be empty.');
        }

        self::$additionalTagOptions[$tag] = $label;
    }

    /** @internal */
    public static function transformOptions(Closure $transformer): void
    {
        self::$optionTransformer = $transformer;
    }

    /** @internal */
    public static function transformTagOptions(Closure $transformer): void
    {
        self::$tagTransformer = $transformer;
    }

    public static function flushState(): void
    {
        self::$additionalCacheOptions = [];
        self::$additionalTagOptions = [];
        self::$optionTransformer = null;
        self::$tagTransformer = null;
    }

    /** @return list<CacheOption> */
    private static function defaultCacheOptions(): array
    {
        $pathService = app(\CraftCms\Cms\Support\Path::class);

        return [
            [
                'key' => 'data',
                'label' => t('Data caches'),
                'info' => t('Anything cached with {method}', [
                    'method' => '`Cache::put`',
                ]),
                'action' => [Cache::getFacadeRoot(), 'clear'],
            ],
            [
                'key' => 'asset',
                'label' => t('Asset caches'),
                'info' => t('Local copies of remote images, generated thumbnails'),
                'action' => function () use ($pathService) {
                    $dirs = [
                        $pathService->assetSources(create: false),
                        $pathService->assetsIcons(create: false),
                        $pathService->imageTransforms(create: false),
                    ];
                    foreach ($dirs as $dir) {
                        File::cleanDirectory($dir);
                    }
                },
            ],
            [
                'key' => 'compiled-templates',
                'label' => t('Compiled templates'),
                'info' => t('Contents of {path}', [
                    'path' => sprintf('`%s/`', File::relativePath($pathService->compiledTemplates(create: false), Aliases::get('@root'))),
                ]),
                'action' => $pathService->compiledTemplates(create: false),
            ],
            [
                'key' => 'compiled-classes',
                'label' => t('Compiled classes'),
                'info' => t('Contents of {path}', [
                    'path' => sprintf('`%s/`', File::relativePath($pathService->compiledClasses(create: false), Aliases::get('@root'))),
                ]),
                'action' => $pathService->compiledClasses(create: false),
            ],
            [
                'key' => 'temp-files',
                'label' => t('Temp files'),
                'info' => t('Contents of {path}', [
                    'path' => sprintf('`%s/`', File::relativePath($pathService->temp(), Aliases::get('@root'))),
                ]),
                'action' => $pathService->temp(),
            ],
            [
                'key' => 'transform-indexes',
                'label' => t('Asset transform index'),
                'info' => t('Record of generated image transforms'),
                'action' => function () {
                    DB::table(Table::IMAGETRANSFORMINDEX)->truncate();
                },
            ],
            [
                'key' => 'asset-indexing-data',
                'label' => t('Asset indexing data'),
                'action' => function () {
                    DB::table(Table::ASSETINDEXDATA)->truncate();
                },
            ],
            [
                'key' => 'ide-helper',
                'label' => t('IDE helper'),
                'info' => t('Contents of {path}', [
                    'path' => sprintf('`%s/`', Cms::config()->ideHelperPath),
                ]),
                'action' => function () {
                    $configPath = Cms::config()->ideHelperPath;
                    $path = Path::isAbsolute($configPath) ? $configPath : base_path($configPath);
                    if (File::isDirectory($path)) {
                        File::cleanDirectory($path);
                    }
                },
            ],
        ];
    }

    /**
     * Returns all cache tag invalidation options.
     */
    /** @return list<TagOption> */
    public static function tagOptions(): array
    {
        $options = [
            'template' => t('Template caches'),
        ];

        if (Cms::config()->enableGql) {
            $options['graphql'] = t('GraphQL queries');
        }

        foreach (self::$additionalTagOptions as $tag => $label) {
            $label = $label instanceof Closure ? app()->call($label) : $label;

            if (! is_string($label)) {
                throw new InvalidArgumentException("Invalid cache tag label [$tag].");
            }

            $options[$tag] = $label;
        }

        $options = collect($options)
            ->map(fn (string $label, string $tag) => compact('tag', 'label'))
            ->values()
            ->all();

        if (isset(self::$tagTransformer)) {
            $options = app()->call(self::$tagTransformer, ['options' => $options]);
        }

        usort($options, fn (array $a, array $b) => $a['label'] <=> $b['label']);

        return $options;
    }
}
