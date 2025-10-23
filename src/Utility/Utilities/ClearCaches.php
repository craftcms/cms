<?php

namespace CraftCms\Cms\Utility\Utilities;

use Craft;
use craft\web\assets\clearcaches\ClearCachesAsset;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Utility\Events\RegisterCacheOptions;
use CraftCms\Cms\Utility\Events\RegisterTagOptions;
use CraftCms\Cms\Utility\Utility;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;

use function CraftCms\Cms\t;

/**
 * ClearCaches represents a ClearCaches dashboard widget.
 */
final class ClearCaches extends Utility
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function displayName(): string
    {
        return t('Caches');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function id(): string
    {
        return 'clear-caches';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function icon(): string
    {
        return 'trash';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function contentHtml(): string
    {
        $cacheOptions = [];
        $tagOptions = [];

        foreach (self::cacheOptions() as $cacheOption) {
            $cacheOptions[] = [
                'label' => $cacheOption['label'],
                'value' => $cacheOption['key'],
                'info' => $cacheOption['info'] ?? null,
            ];
        }

        foreach (self::tagOptions() as $tagOption) {
            $tagOptions[] = [
                'label' => $tagOption['label'],
                'value' => $tagOption['tag'],
            ];
        }

        $cacheOptions = Arr::sort($cacheOptions, 'label');
        $view = Craft::$app->getView();

        $view->registerAssetBundle(ClearCachesAsset::class);
        $view->registerJs('new Craft.ClearCachesUtility(\'clear-caches\');');

        return $view->renderTemplate('_components/utilities/ClearCaches.twig', [
            'cacheOptions' => $cacheOptions,
            'tagOptions' => $tagOptions,
        ]);
    }

    /**
     * Returns all cache options
     */
    public static function cacheOptions(): array
    {
        $pathService = app('Craft')->getPath();

        $options = [
            [
                'key' => 'data',
                'label' => t('Data caches'),
                'info' => t('Anything cached with `Cache::put`'),
                'action' => [Cache::getFacadeRoot(), 'clear'],
            ],
            [
                'key' => 'asset',
                'label' => t('Asset caches'),
                'info' => t('Local copies of remote images, generated thumbnails'),
                'action' => function () use ($pathService) {
                    $dirs = [
                        $pathService->getAssetSourcesPath(false),
                        $pathService->getAssetsIconsPath(false),
                        $pathService->getImageTransformsPath(false),
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
                    'path' => '`storage/runtime/compiled_templates/`',
                ]),
                'action' => $pathService->getCompiledTemplatesPath(false),
            ],
            [
                'key' => 'compiled-classes',
                'label' => t('Compiled classes'),
                'info' => t('Contents of {path}', [
                    'path' => '`storage/runtime/compiled_classes/`',
                ]),
                'action' => $pathService->getCompiledClassesPath(false),
            ],
            [
                'key' => 'cp-resources',
                'label' => t('Control panel resources'),
                'info' => t('Contents of {path}', [
                    'path' => '`web/cpresources/`',
                ]),
                'action' => function () {
                    $basePath = Cms::config()->resourceBasePath;
                    $request = Craft::$app->getRequest();
                    if (
                        $request->getIsConsoleRequest() &&
                        $request->isWebrootAliasSetDynamically &&
                        str_starts_with($basePath, '@webroot')
                    ) {
                        throw new Exception("Unable to clear control panel resources because the location isn't known for console commands.\n".
                            "Explicitly set the @webroot alias in config/general.php to avoid this error.\n".
                            'See https://craftcms.com/docs/6.x/configure.html#aliases for more info.');
                    }

                    $basePath = Craft::getAlias($basePath);
                    if ($basePath !== false && file_exists($basePath)) {
                        if (File::exists($basePath.'/.gitignore')) {
                            $gitignoreContents = File::get($basePath.'/.gitignore');
                        }

                        File::cleanDirectory($basePath);

                        if (isset($gitignoreContents)) {
                            File::put($basePath.'/.gitignore', $gitignoreContents);
                        }
                    }

                    // truncate the resourcepaths table while we're at it
                    DB::table(Table::RESOURCEPATHS)->truncate();
                },
            ],
            [
                'key' => 'temp-files',
                'label' => t('Temp files'),
                'info' => t('Contents of {path}', [
                    'path' => '`storage/runtime/temp/`',
                ]),
                'action' => $pathService->getTempPath(),
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
        ];

        if (Event::hasListeners(RegisterCacheOptions::class)) {
            Event::dispatch($event = new RegisterCacheOptions($options));
            $options = $event->options;
        }

        return Arr::sort($options, 'label');
    }

    /**
     * Returns all cache tag invalidation options.
     */
    public static function tagOptions(): array
    {
        $options = [
            [
                'tag' => 'template',
                'label' => t('Template caches'),
            ],
        ];

        if (Cms::config()->enableGql) {
            $options[] = [
                'tag' => 'graphql',
                'label' => t('GraphQL queries'),
            ];
        }

        if (Event::hasListeners(RegisterTagOptions::class)) {
            Event::dispatch($event = new RegisterTagOptions($options));
            $options = $event->options;
        }

        return Arr::sort($options, 'label');
    }
}
