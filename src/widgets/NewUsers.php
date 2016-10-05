<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\widgets;

use Craft;
use craft\app\base\Widget;
use craft\app\helpers\Json;

/**
 * NewUsers represents a New Users dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class NewUsers extends Widget
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName()
    {
        return Craft::t('app', 'New Users');
    }

    /**
     * @inheritdoc
     */
    public static function isSelectable()
    {
        // This widget is only available for Craft Pro
        return (Craft::$app->getEdition() == Craft::Pro);
    }

    // Properties
    // =========================================================================

    /**
     * @var integer The ID of the user group
     */
    public $userGroupId;

    /**
     * @var string The date range
     */
    public $dateRange;


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        if ($groupId = $this->userGroupId)
        {
            $userGroup = Craft::$app->getUserGroups()->getGroupById($groupId);

            if ($userGroup)
            {
                return Craft::t('app', 'New Users').' – '.Craft::t('app', $userGroup->name);
            }
        }

        return parent::getTitle();
    }

    /**
     * @inheritdoc
     */
    public function getBodyHtml()
    {
        if (Craft::$app->getEdition() != Craft::Pro)
        {
            return false;
        }

        $options = $this->getSettings();
        $options['orientation'] = Craft::$app->getLocale()->getOrientation();

        Craft::$app->getView()->registerJsResource('js/NewUsersWidget.js');
        Craft::$app->getView()->registerJs('new Craft.NewUsersWidget('.$this->id.', '.Json::encode($options).');');

        return '<div></div>';
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('_components/widgets/NewUsers/settings',
            [
                'widget' => $this
            ]);
    }

    /**
     * @inheritdoc
     */
    public function getIconPath()
    {
        return Craft::$app->getPath()->getResourcesPath().'/images/widgets/new-users.svg';
    }
}
