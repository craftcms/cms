<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;

/**
 * UnsuspendUsers represents an Unsuspend Users element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UnsuspendUsers extends ElementAction
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Unsuspend');
    }

    /**
     * Performs the action on any elements that match the given criteria.
     *
     * @param ElementQueryInterface $query The element query defining which elements the action should affect.
     * @return bool Whether the action was performed successfully.
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        // Get the users that are suspended
        $query->status(User::STATUS_SUSPENDED);
        /** @var User[] $users */
        $users = $query->all();

        foreach ($users as $user) {
            Craft::$app->getUsers()->unsuspendUser($user);
        }

        $this->setMessage(Craft::t('app', 'Users unsuspended.'));

        return true;
    }
}
