<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use Craft;
use craft\web\assets\upgrade\UpgradeAsset;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Utility\Utility;

use function CraftCms\Cms\t;

/**
 * Upgrade utility
 */
final class Upgrade extends Utility
{
    #[\Override]
    public static function displayName(): string
    {
        return t('Craft {version} Upgrade', [
            'version' => (int) Cms::VERSION + 1,
        ]);
    }

    #[\Override]
    public static function id(): string
    {
        return 'upgrade';
    }

    #[\Override]
    public static function icon(): string
    {
        return 'square-arrow-up';
    }

    #[\Override]
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

        $version = (int) Cms::VERSION + 1;
        $view->registerJsWithVars(fn ($args) => <<<JS
window.upgardeUtility = new Craft.UpgradeUtility(...$args)
JS, [
            [$version, $allPlugins],
        ]);

        return $view->renderTemplate('_components/utilities/Upgrade.twig');
    }
}
