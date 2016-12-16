<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\tools;

use Craft;
use craft\base\Tool;
use craft\events\RegisterCacheOptionsEvent;
use craft\helpers\FileHelper;
use yii\base\Event;

/**
 * ClearCaches represents a Clear Caches tool.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class ClearCaches extends Tool
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
    public static function displayName()
    {
        return Craft::t('app', 'Clear Caches');
    }

    /**
     * @inheritdoc
     */
    public static function iconValue()
    {
        return 'trash';
    }

    /**
     * @inheritdoc
     */
    public static function optionsHtml()
    {
        $options = [];

        foreach (self::_getAllCacheOptions() as $cacheOption) {
            $options[] = [
                'label' => $cacheOption['label'],
                'value' => $cacheOption['key']
            ];
        }

        return Craft::$app->getView()->renderTemplate('_includes/forms/checkboxSelect', [
            'name' => 'caches',
            'options' => $options
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function buttonLabel()
    {
        return Craft::t('app', 'Clear!');
    }

    /**
     * Returns the cache folders we allow to be cleared as well as any plugin cache paths that have used the
     * 'registerCachePaths' hook.
     *
     * @return array
     */
    private static function _getAllCacheOptions()
    {
        $runtimePath = Craft::$app->getPath()->getRuntimePath();

        $options = [
            [
                'key' => 'data',
                'label' => Craft::t('app', 'Data caches'),
                'action' => [Craft::$app->getCache(), 'flush']
            ],
            [
                'key' => 'asset',
                'label' => Craft::t('app', 'Asset caches'),
                'action' => $runtimePath.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'cache'
            ],
            [
                'key' => 'rss',
                'label' => Craft::t('app', 'RSS caches'),
                'action' => $runtimePath.DIRECTORY_SEPARATOR.'cache'
            ],
            [
                'key' => 'compiled-templates',
                'label' => Craft::t('app', 'Compiled templates'),
                'action' => $runtimePath.DIRECTORY_SEPARATOR.'compiled_templates'
            ],
            [
                'key' => 'temp-files',
                'label' => Craft::t('app', 'Temp files'),
                'action' => $runtimePath.DIRECTORY_SEPARATOR.'temp'
            ],
            [
                'key' => 'transform-indexes',
                'label' => Craft::t('app', 'Asset transform index'),
                'action' => function() {
                    Craft::$app->getDb()->createCommand()
                        ->truncateTable('{{%assettransformindex}}')
                        ->execute();
                }
            ],
            [
                'key' => 'asset-indexing-data',
                'label' => Craft::t('app', 'Asset indexing data'),
                'action' => function() {
                    Craft::$app->getDb()->createCommand()
                        ->truncateTable('{{%assetindexdata}}')
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

        return $options;
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function performAction(array $params)
    {
        if (!isset($params['caches'])) {
            return;
        }

        foreach (self::_getAllCacheOptions() as $cacheOption) {
            if (is_array($params['caches']) && !in_array($cacheOption['key'], $params['caches'], true)) {
                continue;
            }

            $action = $cacheOption['action'];

            if (is_string($action)) {
                try {
                    FileHelper::clearDirectory($action);
                } catch (\Exception $e) {
                    Craft::warning("Could not clear the directory {$action}: ".$e->getMessage());
                }
            } else if (isset($cacheOption['params'])) {
                call_user_func_array($action, $cacheOption['params']);
            } else {
                call_user_func($action);
            }
        }
    }
}
