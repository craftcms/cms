<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\utilities;

use Craft;
use craft\base\Utility;
use craft\db\Table;
use craft\events\RegisterCacheOptionsEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\FileHelper;
use craft\web\assets\clearcaches\ClearCachesAsset;
use yii\base\Event;
use yii\base\InvalidArgumentException;

/**
 * ClearCaches represents a ClearCaches dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class ClearCaches extends Utility
{
    /**
     * @event RegisterCacheOptionsEvent The event that is triggered when registering cache options.
     *
     * Each option added to [[RegisterCacheOptionsEvent::$options]] should be an array that has the following keys:
     *
     * - `key` – An identifying key for the cache option.
     * - `label` – A human-facing label for the cache option.
     * - `action` – Either the path to a folder that should be cleared, or a callable that should handle the cache clearing.
     * - `info` _(optional)_ – A description of the cache option.
     *
     * @see cacheOptions()
     */
    const EVENT_REGISTER_CACHE_OPTIONS = 'registerCacheOptions';

    /**
     * @event RegisterCacheOptionsEvent The event that is triggered when registering cache tag invalidation options.
     *
     * Each option added to [[RegisterCacheOptionsEvent::$options]] should be an array that has the following keys:
     *
     * - `tag` – The cache tag name that sholud be cleared.
     * - `label` – A human-facing label for the cache tag option.
     *
     * @see tagOptions()
     * @since 3.5.0
     */
    const EVENT_REGISTER_TAG_OPTIONS = 'registerTagOptions';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Caches');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'clear-caches';
    }

    /**
     * @inheritdoc
     */
    public static function iconPath()
    {
        return Craft::getAlias('@appicons/trash.svg');
    }

    /**
     * @inheritdoc
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

        ArrayHelper::multisort($cacheOptions, 'label');
        $view = Craft::$app->getView();

        $view->registerAssetBundle(ClearCachesAsset::class);
        $view->registerJs('new Craft.ClearCachesUtility(\'clear-caches\');');

        return $view->renderTemplate('_components/utilities/ClearCaches', [
            'cacheOptions' => $cacheOptions,
            'tagOptions' => $tagOptions,
        ]);
    }

    /**
     * Returns all cache options
     *
     * @return array
     */
    public static function cacheOptions(): array
    {
        $pathService = Craft::$app->getPath();

        $options = [
            [
                'key' => 'data',
                'label' => Craft::t('app', 'Data caches'),
                'info' => Craft::t('app', 'Anything cached with `Craft::$app->cache->set()`'),
                'action' => [Craft::$app->getCache(), 'flush']
            ],
            [
                'key' => 'asset',
                'label' => Craft::t('app', 'Asset caches'),
                'info' => Craft::t('app', 'Local copies of remote images, generated thumbnails'),
                'action' => function() use ($pathService) {
                    $dirs = [
                        $pathService->getAssetSourcesPath(false),
                        $pathService->getAssetThumbsPath(false),
                        $pathService->getAssetsIconsPath(false),
                    ];
                    foreach ($dirs as $dir) {
                        try {
                            FileHelper::clearDirectory($dir);
                        } catch (InvalidArgumentException $e) {
                            // the directory doesn't exist
                        }
                    }
                }
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
                'key' => 'cp-resources',
                'label' => Craft::t('app', 'Control panel resources'),
                'info' => Craft::t('app', 'Contents of {path}', [
                    'path' => '`web/cpresources/`',
                ]),
                'action' => function() {
                    $basePath = Craft::$app->getConfig()->getGeneral()->resourceBasePath;
                    $request = Craft::$app->getRequest();
                    if (
                        $request->getIsConsoleRequest() &&
                        $request->isWebrootAliasSetDynamically &&
                        strpos($basePath, '@webroot') === 0
                    ) {
                        throw new \Exception("Unable to clear control panel resources because the location isn't known for console commands.\n" .
                            "Explicitly set the @webroot alias in config/general.php to avoid this error.\n" .
                            'See https://craftcms.com/docs/3.x/config/#aliases for more info.');
                    }

                    FileHelper::clearDirectory(Craft::getAlias($basePath), [
                        'except' => ['.gitignore']
                    ]);
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
                'action' => function() {
                    Craft::$app->getDb()->createCommand()
                        ->truncateTable(Table::ASSETTRANSFORMINDEX)
                        ->execute();
                }
            ],
            [
                'key' => 'asset-indexing-data',
                'label' => Craft::t('app', 'Asset indexing data'),
                'action' => function() {
                    Craft::$app->getDb()->createCommand()
                        ->truncateTable(Table::ASSETINDEXDATA)
                        ->execute();
                }
            ],
        ];

        $event = new RegisterCacheOptionsEvent([
            'options' => $options
        ]);
        Event::trigger(self::class, self::EVENT_REGISTER_CACHE_OPTIONS, $event);

        ArrayHelper::multisort($event->options, 'label');

        return $event->options;
    }

    /**
     * Returns all cache tag invalidation options.
     *
     * @return array
     * @since 3.5.0
     */
    public static function tagOptions(): array
    {
        $options = [
            [
                'tag' => 'template',
                'label' => Craft::t('app', 'Template caches'),
            ],
        ];

        if (Craft::$app->getConfig()->getGeneral()->enableGql) {
            $options[] = [
                'tag' => 'graphql',
                'label' => Craft::t('app', 'GraphQL queries'),
            ];
        }

        $event = new RegisterCacheOptionsEvent([
            'options' => $options
        ]);
        Event::trigger(self::class, self::EVENT_REGISTER_TAG_OPTIONS, $event);

        ArrayHelper::multisort($event->options, 'label');

        return $event->options;
    }
}
