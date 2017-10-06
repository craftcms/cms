<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\widgets;

use Craft;
use craft\base\Plugin;
use craft\base\Widget;
use craft\helpers\Json;
use craft\web\assets\craftsupport\CraftSupportAsset;
use PDO;

/**
 * CraftSupport represents a Craft Support dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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

        $plugins = '';
        foreach (Craft::$app->getPlugins()->getAllPlugins() as $plugin) {
            /** @var Plugin $plugin */
            $plugins .= "\n    - ".$plugin->name.' '.$plugin->getVersion();
        }

        $db = Craft::$app->getDb();
        if ($db->getIsMysql()) {
            $driver = 'MySQL';
        } else {
            $driver = 'PostgreSQL';
        }

        $envInfoJs = Json::encode([
            'Craft version' => Craft::$app->getVersion().' ('.Craft::$app->getEditionName().')',
            'PHP version' => str_replace('~', '\~', PHP_VERSION),
            'Database driver & version' => $driver.' '.str_replace('~', '\~', $db->getMasterPdo()->getAttribute(PDO::ATTR_SERVER_VERSION)),
            'Plugins & versions' => $plugins,
        ]);

        $js = "new Craft.CraftSupportWidget({$this->id}, {$envInfoJs});";
        $view->registerJs($js);

        $view->registerAssetBundle(CraftSupportAsset::class);
        $view->registerTranslations('app', [
            'Message sent successfully.',
        ]);

        $iconsDir = Craft::getAlias('@app/icons');

        // Only show the DB backup option if DB backups haven't been disabled
        $showBackupOption = (Craft::$app->getConfig()->getGeneral()->backupCommand !== false);

        return $view->renderTemplate('_components/widgets/CraftSupport/body', [
            'widget' => $this,
            'buoeyIcon' => file_get_contents($iconsDir.'/buoey.svg'),
            'bullhornIcon' => file_get_contents($iconsDir.'/bullhorn.svg'),
            'seIcon' => file_get_contents($iconsDir.'/craft-stack-exchange.svg'),
            'ghIcon' => file_get_contents($iconsDir.'/github.svg'),
            'showBackupOption' => $showBackupOption
        ]);
    }
}
