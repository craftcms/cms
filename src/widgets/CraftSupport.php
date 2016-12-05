<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\widgets;

use Craft;
use craft\base\Widget;

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
    public static function displayName()
    {
        return Craft::t('app', 'Craft Support');
    }

    /**
     * @inheritdoc
     */
    public static function isSelectable()
    {
        // Only admins get the Craft Support widget.
        return (parent::isSelectable() && Craft::$app->getUser()->getIsAdmin());
    }

    /**
     * @inheritdoc
     */
    protected static function allowMultipleInstances()
    {
        return false;
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getIconPath()
    {
        return Craft::$app->getPath()->getResourcesPath().DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'widgets'.DIRECTORY_SEPARATOR.'craft-support.svg';
    }

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return Craft::t('app', 'Send a message to Craft Support');
    }

    /**
     * @inheritdoc
     */
    public function getBodyHtml()
    {
        // Only admins get the Craft Support widget.
        if (!Craft::$app->getUser()->getIsAdmin()) {
            return false;
        }

        $js = "new Craft.CraftSupportWidget({$this->id});";
        Craft::$app->getView()->registerJs($js);

        Craft::$app->getView()->registerJsResource('js/CraftSupportWidget.js');
        Craft::$app->getView()->registerTranslations('app', [
            'Message sent successfully.',
            'Couldn’t send support request.',
        ]);

        // Only show the DB backup option if DB backups haven't been disabled
        $showBackupOption = (Craft::$app->getConfig()->get('backupCommand') !== false);

        return Craft::$app->getView()->renderTemplate('_components/widgets/CraftSupport/body', [
            'showBackupOption' => $showBackupOption
        ]);
    }
}
