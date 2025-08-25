<?php

namespace CraftCms\Cms\Utility\Utilities;

use Craft;
use craft\web\assets\upgrade\UpgradeAsset;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Utility\Utility;

/**
 * Upgrade utility
 *
 * @since 6.0.0
 */
final class Upgrade extends Utility
{
    /**
     * {@inheritdoc}
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Craft {version} Upgrade', [
            'version' => (int) Craft::$app->version + 1,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function id(): string
    {
        return 'upgrade';
    }

    /**
     * {@inheritdoc}
     */
    public static function icon(): string
    {
        return 'square-arrow-up';
    }

    /**
     * {@inheritdoc}
     */
    public static function contentHtml(): string
    {
        $view = Craft::$app->getView();
        $view->registerAssetBundle(UpgradeAsset::class);

        $pluginsService = app(Plugins::class);
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

        $version = (int) Craft::$app->version + 1;
        $view->registerJsWithVars(fn ($args) => <<<JS
window.upgardeUtility = new Craft.UpgradeUtility(...$args);
JS, [
            [$version, $allPlugins],
        ]);

        return $view->renderTemplate('_components/utilities/Upgrade.twig');
    }
}
