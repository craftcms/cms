<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\elements\actions;

use Craft;
use craft\app\base\ElementAction;
use craft\app\elements\db\ElementQueryInterface;
use yii\base\Exception;

/**
 * DeleteAssets represents a Delete Assets element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class DeleteAssets extends ElementAction
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTriggerLabel()
    {
        return Craft::t('app', 'Delete…');
    }

    /**
     * @inheritdoc
     */
    public static function isDestructive()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getConfirmationMessage()
    {
        return Craft::t('app', 'Are you sure you want to delete the selected assets?');
    }

    /**
     * @inheritdoc
     */
    public function performAction(ElementQueryInterface $query)
    {

        try {
            Craft::$app->getAssets()->deleteAssetsByIds($query->ids());
            $this->setMessage(Craft::t('app', 'Assets deleted.'));
        } catch (Exception $exception) {
            $this->setMessage($exception->getMessage());
            return false;
        }

        return true;
    }
}
