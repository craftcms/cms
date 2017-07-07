<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\utilities;

use Craft;
use craft\base\Utility;
use craft\web\assets\updates\UpdatesAsset;

/**
 * Updates represents a Updates dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Updates extends Utility
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Updates');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'updates';
    }

    /**
     * @inheritdoc
     */
    public static function iconPath()
    {
        return Craft::getAlias('@app/icons/excite.svg');
    }

    /**
     * @inheritdoc
     */
    public static function badgeCount(): int
    {
        $updatesService = Craft::$app->getUpdates();

        if ($updatesService->getIsUpdateInfoCached() === false) {
            return 0;
        }

        return $updatesService->getTotalAvailableUpdates();
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        $view = Craft::$app->getView();
        $view->registerAssetBundle(UpdatesAsset::class);
        $view->registerTranslations('app', [
            'You’re all up-to-date!',
            'Critical',
            'Update',
            'Update to {version}',
            'Update all',
            'Craft’s <a href="http://craftcms.com/license" target="_blank">Terms and Conditions</a> have changed.',
            'I agree.',
            'Seriously, update.',
            'Show all',
        ]);

        $view->registerJs('new Craft.UpdatesUtility();');

        return $view->renderTemplate('_components/utilities/Updates');
    }
}
