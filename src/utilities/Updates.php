<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\utilities;

use Craft;
use craft\base\Utility;
use craft\helpers\App;
use craft\helpers\Json;
use yii\base\Exception;

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
        $iconPath = Craft::getAlias('@app/icons/excite.svg');

        if ($iconPath === false) {
            throw new Exception('There was a problem getting the icon path.');
        }

        return $iconPath;
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
        $view->registerCssResource('css/updates.css');
        $view->registerJsResource('js/UpdatesUtility.js');
        $view->registerTranslations('app', [
            'You’ve got updates!',
            'You’re all up-to-date!',
            'Critical',
            'Update',
            'Download',
            'Craft’s <a href="http://craftcms.com/license" target="_blank">Terms and Conditions</a> have changed.',
            'I agree.',
            'Seriously, download.',
            'Seriously, update.',
            'Install',
            '{app} update required',
            'Released on {date}',
            'Show more',
            'Added',
            'Improved',
            'Fixed',
            'Download',
            'Use Composer to get this update.',
        ]);

        $isComposerInstallJs = Json::encode(App::isComposerInstall());
        $js = <<<EOD
//noinspection JSUnresolvedVariable
new Craft.UpdatesUtility({
    isComposerInstall: {$isComposerInstallJs}
});
EOD;
        $view->registerJs($js);

        return $view->renderTemplate('_components/utilities/Updates');
    }
}
