<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\widgets;

use Craft;
use craft\base\Plugin;
use craft\base\Widget;
use craft\helpers\App;
use craft\helpers\Json;
use craft\web\assets\craftsupport\CraftSupportAsset;

/**
 * CraftSupport represents a Craft Support dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class CraftSupport extends Widget
{
    // Static
    // =========================================================================

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
    public static function iconPath()
    {
        return Craft::getAlias('@app/icons/buoey.svg');
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getBodyHtml()
    {
        // Only admins get the Craft Support widget.
        if (!Craft::$app->getUser()->getIsAdmin()) {
            return false;
        }

        $view = Craft::$app->getView();
        $view->registerAssetBundle(CraftSupportAsset::class);

        $plugins = '';
        foreach (Craft::$app->getPlugins()->getAllPlugins() as $plugin) {
            /** @var Plugin $plugin */
            $plugins .= "\n    - " . $plugin->name . ' ' . $plugin->getVersion();
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

        $envInfoJs = Json::encode([
            'Craft version' => Craft::$app->getVersion() . ' (' . Craft::$app->getEditionName() . ')',
            'PHP version' => App::phpVersion(),
            'OS version' => PHP_OS . ' ' . php_uname('r'),
            'Database driver & version' => $dbDriver . ' ' . $db->getVersion(),
            'Image driver & version' => $imageDriver . ' ' . $imagesService->getVersion(),
            'Plugins & versions' => $plugins,
        ]);

        $js = "new Craft.CraftSupportWidget({$this->id}, {$envInfoJs});";
        $view->registerJs($js);

        $iconsDir = Craft::getAlias('@app/icons');

        // Only show the DB backup option if DB backups haven't been disabled
        $showBackupOption = (Craft::$app->getConfig()->getGeneral()->backupCommand !== false);

        return $view->renderTemplate('_components/widgets/CraftSupport/body', [
            'widget' => $this,
            'buoeyIcon' => file_get_contents($iconsDir . '/buoey.svg'),
            'bullhornIcon' => file_get_contents($iconsDir . '/bullhorn.svg'),
            'seIcon' => file_get_contents($iconsDir . '/craft-stack-exchange.svg'),
            'ghIcon' => file_get_contents($iconsDir . '/github.svg'),
            'showBackupOption' => $showBackupOption
        ]);
    }
}
