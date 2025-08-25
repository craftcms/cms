<?php

namespace CraftCms\Cms\Utility\Utilities;

use Craft;
use craft\web\assets\clearcaches\ClearCachesAsset;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Db\Table;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Utility\Events\RegisterCacheOptions;
use CraftCms\Cms\Utility\Events\RegisterTagOptions;
use CraftCms\Cms\Utility\Utility;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;

/**
 * ClearCaches represents a ClearCaches dashboard widget.
 *

 * @since 6.0.0
 */
final class ClearCaches extends Utility
{
    /**
     * {@inheritdoc}
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Caches');
    }

    /**
     * {@inheritdoc}
     */
    public static function id(): string
    {
        return 'clear-caches';
    }

    /**
     * {@inheritdoc}
     */
    public static function icon(): string
    {
        return 'trash';
    }

    /**
     * {@inheritdoc}
     */
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
        $pathService = Craft::$app->getPath();

        $options = [
            [
                'key' => 'data',
                'label' => Craft::t('app', 'Data caches'),
                'info' => Craft::t('app', 'Anything cached with `Cache::put`'),
                'action' => [Cache::getFacadeRoot(), 'clear'],
            ],
            [
                'key' => 'asset',
                'label' => Craft::t('app', 'Asset caches'),
                'info' => Craft::t('app', 'Local copies of remote images, generated thumbnails'),
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
                'label' => Craft::t('app', 'Compiled templates'),
                'info' => Craft::t('app', 'Contents of {path}', [
                    'path' => '`storage/runtime/compiled_templates/`',
                ]),
                'action' => $pathService->getCompiledTemplatesPath(false),
            ],
            [
                'key' => 'compiled-classes',
                'label' => Craft::t('app', 'Compiled classes'),
                'info' => Craft::t('app', 'Contents of {path}', [
                    'path' => '`storage/runtime/compiled_classes/`',
                ]),
                'action' => $pathService->getCompiledClassesPath(false),
            ],
            [
                'key' => 'cp-resources',
                'label' => Craft::t('app', 'Control panel resources'),
                'info' => Craft::t('app', 'Contents of {path}', [
                    'path' => '`web/cpresources/`',
                ]),
                'action' => function () {
                    $basePath = app(GeneralConfig::class)->resourceBasePath;
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
                'label' => Craft::t('app', 'Temp files'),
                'info' => Craft::t('app', 'Contents of {path}', [
                    'path' => '`storage/runtime/temp/`',
                ]),
                'action' => $pathService->getTempPath(),
            ],
            [
                'key' => 'transform-indexes',
                'label' => Craft::t('app', 'Asset transform index'),
                'info' => Craft::t('app', 'Record of generated image transforms'),
                'action' => function () {
                    DB::table(Table::IMAGETRANSFORMINDEX)->truncate();
                },
            ],
            [
                'key' => 'asset-indexing-data',
                'label' => Craft::t('app', 'Asset indexing data'),
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
                'label' => Craft::t('app', 'Template caches'),
            ],
        ];

        if (app(GeneralConfig::class)->enableGql) {
            $options[] = [
                'tag' => 'graphql',
                'label' => Craft::t('app', 'GraphQL queries'),
            ];
        }

        if (Event::hasListeners(RegisterTagOptions::class)) {
            Event::dispatch($event = new RegisterTagOptions($options));
            $options = $event->options;
        }

        return Arr::sort($options, 'label');
    }
}
