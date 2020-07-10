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
use Symfony\Component\Yaml\Yaml;
use yii\base\Event;
use yii\base\InvalidArgumentException;

/**
 * ProjectConfig represents a ProjectConfig utility.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class ProjectConfig extends Utility
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Project Config');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'project-config';
    }

    /**
     * @inheritdoc
     */
    public static function iconPath()
    {
        return Craft::getAlias('@app/icons/tools.svg');
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        $options = [
            ['label' => 'Apply project config changes', 'value' => 'apply'],
            ['label' => 'Re-apply entire project config', 'value' => 'apply-force'],
            ['label' => 'Rebuild Project Config', 'value' => 'rebuild'],
        ];

        $entireConfig = Yaml::dump(Craft::$app->getProjectConfig()->get(), 20, 2);

        $view = Craft::$app->getView();

        $view->registerAssetBundle(ClearCachesAsset::class);
        $view->registerJs('new Craft.ClearCachesUtility(\'clear-caches\');');

        return $view->renderTemplate('_components/utilities/ProjectConfig', [
            'options' => $options,
            'entireConfig' => $entireConfig,
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
                        throw new \Exception('Unable to clear control panel resources because the location isn\'t known for console commands.');
                    }

                    FileHelper::clearDirectory(Craft::getAlias($basePath), [
                        'except' => ['/.gitignore']
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
            [
                'key' => 'graphql-caches',
                'label' => Craft::t('app', 'GraphQL caches'),
                'action' => [Craft::$app->getGql(), 'invalidateCaches']
            ],
        ];

        $event = new RegisterCacheOptionsEvent([
            'options' => $options
        ]);

        Event::trigger(self::class, self::EVENT_REGISTER_CACHE_OPTIONS, $event);

        return $event->options;
    }
}
