<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\widgets;

use Craft;
use craft\base\Widget;
use craft\helpers\App;
use craft\web\assets\craftsupport\CraftSupportAsset;

/**
 * CraftSupport represents a Craft Support dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class CraftSupport extends Widget
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Craft Support');
    }

    /**
     * @inheritdoc
     */
    public static function isSelectable(): bool
    {
        // Only admins get the Craft Support widget.
        return (parent::isSelectable() && Craft::$app->getUser()->getIsAdmin());
    }

    /**
     * @inheritdoc
     */
    protected static function allowMultipleInstances(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function icon(): ?string
    {
        return Craft::getAlias('@appicons/buoey.svg');
    }

    /**
     * @inheritdoc
     */
    public function getTitle(): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getBodyHtml(): ?string
    {
        // Only admins get the Craft Support widget.
        if (!Craft::$app->getUser()->getIsAdmin()) {
            return null;
        }

        $view = Craft::$app->getView();
        $assetBundle = $view->registerAssetBundle(CraftSupportAsset::class);

        $cmsVersion = Craft::$app->getVersion();
        $cmsMajorVersion = (int)$cmsVersion;

        $pluginVersions = [];
        foreach (Craft::$app->getPlugins()->getAllPlugins() as $plugin) {
            $pluginVersions[] = sprintf('- %s %s', $plugin->name, $plugin->getVersion());
        }

        $db = Craft::$app->getDb();
        if ($db->getIsMysql()) {
            $dbDriver = 'MySQL';
        } else {
            $dbDriver = 'PostgreSQL';
        }

        $imagesService = Craft::$app->getImages();
        if ($imagesService->getIsGd()) {
            $imageDriver = 'GD';
        } else {
            $imageDriver = 'Imagick';
        }

        $body = <<<EOD
### Description



### Steps to reproduce

1.

### Expected behavior



### Actual behavior


EOD;

        $view->registerJsWithVars(function($id, $settings) {
            return <<<JS
new Craft.CraftSupportWidget($id, $settings);
JS;
        }, [
            $this->id,
            [
                'issueTitlePrefix' => sprintf("[%s.x]: ", $cmsMajorVersion),
                'issueParams' => [
                    'labels' => sprintf("bug,craft%s", $cmsMajorVersion),
                    'template' => sprintf("BUG-REPORT-V%s.yml", $cmsMajorVersion),
                    'body' => $body,
                    'cmsVersion' => sprintf('%s (%s)', $cmsVersion, Craft::$app->getEditionName()),
                    'phpVersion' => App::phpVersion(),
                    'os' => sprintf('%s %s', PHP_OS, php_uname('r')),
                    'db' => sprintf('%s %s', $dbDriver, App::normalizeVersion($db->getSchema()->getServerVersion())),
                    'imageDriver' => sprintf('%s %s', $imageDriver, $imagesService->getVersion()),
                    'plugins' => implode("\n", $pluginVersions),
                ],
            ],
        ]);

        $iconsDir = Craft::getAlias('@appicons');

        // Only show the DB backup option if DB backups haven't been disabled
        $showBackupOption = (Craft::$app->getConfig()->getGeneral()->backupCommand !== false);

        return $view->renderTemplate('_components/widgets/CraftSupport/body.twig', [
            'widget' => $this,
            'buoeyIcon' => file_get_contents($iconsDir . '/buoey.svg'),
            'bullhornIcon' => file_get_contents($iconsDir . '/bullhorn.svg'),
            'seIcon' => file_get_contents($iconsDir . '/craft-stack-exchange.svg'),
            'ghIcon' => file_get_contents($iconsDir . '/github.svg'),
            'showBackupOption' => $showBackupOption,
            'bundleUrl' => $assetBundle->baseUrl,
        ]);
    }
}
