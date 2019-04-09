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
 * @since 3.0
 */
class ClearCaches extends Utility
{
    // Constants
    // =========================================================================

    /**
     * @event RegisterCacheOptionsEvent The event that is triggered when registering cache options.
     */
    const EVENT_REGISTER_CACHE_OPTIONS = 'registerCacheOptions';

    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Clear Caches');
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
        return Craft::getAlias('@app/icons/trash.svg');
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        $options = [];

        foreach (self::cacheOptions() as $cacheOption) {
            $options[] = [
                'label' => $cacheOption['label'],
                'value' => $cacheOption['key']
            ];
        }

        ArrayHelper::multisort($options, 'label');
        $view = Craft::$app->getView();

        $view->registerAssetBundle(ClearCachesAsset::class);
        $view->registerJs('new Craft.ClearCachesUtility(\'clear-caches\');');

        return $view->renderTemplate('_components/utilities/ClearCaches', [
            'options' => $options,
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
                'action' => [Craft::$app->getCache(), 'flush']
            ],
            [
                'key' => 'asset',
                'label' => Craft::t('app', 'Asset caches'),
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
                'action' => $pathService->getCompiledTemplatesPath(false),
            ],
            [
                'key' => 'cp-resources',
                'label' => Craft::t('app', 'Control Panel resources'),
                'action' => function() {
                    $basePath = Craft::$app->getConfig()->getGeneral()->resourceBasePath;
                    $request = Craft::$app->getRequest();
                    if (
                        $request->getIsConsoleRequest() &&
                        $request->isWebrootAliasSetDynamically &&
                        strpos($basePath, '@webroot') === 0
                    ) {
                        throw new \Exception('Unable to clear Control Panel resources because the location isn\'t known for console commands.');
                    }

                    FileHelper::clearDirectory(Craft::getAlias($basePath), [
                        'except' => ['/.gitignore']
                    ]);
                },
            ],
            [
                'key' => 'temp-files',
                'label' => Craft::t('app', 'Temp files'),
                'action' => $pathService->getTempPath(),
            ],
            [
                'key' => 'transform-indexes',
                'label' => Craft::t('app', 'Asset transform index'),
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
                'key' => 'template-caches',
                'label' => Craft::t('app', 'Template caches'),
                'action' => [Craft::$app->getTemplateCaches(), 'deleteAllCaches']
            ],
        ];

        $event = new RegisterCacheOptionsEvent([
            'options' => $options
        ]);

        Event::trigger(self::class, self::EVENT_REGISTER_CACHE_OPTIONS, $event);

        return $event->options;
    }
}
