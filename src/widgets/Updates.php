<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\widgets;

use Craft;
use craft\base\Widget;
use craft\web\assets\updateswidget\UpdatesWidgetAsset;

/**
 * Updates represents an Updates dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Updates extends Widget
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
    public static function isSelectable(): bool
    {
        // Gotta have update permission to get this widget
        return (parent::isSelectable() && Craft::$app->getUser()->checkPermission('performUpdates'));
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
        return Craft::getAlias('@app/icons/excite.svg');
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getBodyHtml()
    {
        // Make sure the user actually has permission to perform updates
        if (!Craft::$app->getUser()->checkPermission('performUpdates')) {
            return false;
        }

        $view = Craft::$app->getView();
        $cached = Craft::$app->getUpdates()->getIsUpdateInfoCached();

        if (!$cached || !Craft::$app->getUpdates()->getTotalAvailableUpdates()) {
            $view->registerAssetBundle(UpdatesWidgetAsset::class);
            $view->registerJs('new Craft.UpdatesWidget('.$this->id.', '.($cached ? 'true' : 'false').');');
        }

        if ($cached) {
            return $view->renderTemplate('_components/widgets/Updates/body',
                [
                    'total' => Craft::$app->getUpdates()->getTotalAvailableUpdates()
                ]);
        }

        return '<p class="centeralign">'.Craft::t('app', 'Checking for updates…').'</p>';
    }
}
