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
use CraftCms\Cms\Cms;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Facades\HtmlStack;
use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * Upgrade utility
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.7.40
 * @deprecated in 6.0.0.
 */
class Upgrade extends Utility
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return t('Craft {version} Upgrade', [
            'version' => (int) Cms::VERSION + 1,
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
    public static function icon(): ?string
    {
        return 'square-arrow-up';
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        Craft::$app->getView()->registerAssetBundle(UpgradeAsset::class);

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
        HtmlStack::jsWithVars(fn($args) => <<<JS
window.upgradeUtility = new Craft.UpgradeUtility(...$args)
JS, [
            [$version, $allPlugins],
        ]);

        return template('_components/utilities/Upgrade');
    }
}
