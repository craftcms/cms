<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\utilities;

use Craft;
use craft\base\Utility;
use craft\web\assets\upgrade\UpgradeAsset;

/**
 * Upgrade utility
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.7.40
 */
class Upgrade extends Utility
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Craft {version} Upgrade', [
            'version' => (int)Craft::$app->version + 1,
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'upgrade';
    }

    /**
     * @inheritdoc
     */
    public static function iconPath(): ?string
    {
        return Craft::getAlias('@appicons/upgrade.svg');
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        $view = Craft::$app->getView();
        $view->registerAssetBundle(UpgradeAsset::class);

        $pluginsService = Craft::$app->getPlugins();
        $allPlugins = [];
        foreach ($pluginsService->getAllPluginInfo() as $handle => $info) {
            $allPlugins[] = [
                'name' => $info['name'],
                'handle' => $handle,
                'developerName' => $info['developer'] ?? null,
                'developerUrl' => $info['developerUrl'] ?? null,
                'icon' => $pluginsService->getPluginIconSvg($handle),
                'isInstalled' => $info['isInstalled'],
            ];
        }

        $version = (int)Craft::$app->version + 1;
        $view->registerJsWithVars(function($args) {
            return <<<JS
window.upgardeUtility = new Craft.UpgradeUtility(...$args);
JS;
        }, [
            [$version, $allPlugins],
        ]);

        return $view->renderTemplate('_components/utilities/Upgrade.twig');
    }
}
