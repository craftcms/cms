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

        $version = (int)Craft::$app->version + 1;
        $view->registerJsWithVars(function($version) {
            return <<<JS
new Craft.UpgradeUtility($version);
JS;
        }, [$version]);

        return $view->renderTemplate('_components/utilities/Upgrade');
    }
}
