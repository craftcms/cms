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
 * @since 3.0.0
 */
class UnsuspendUsers extends ElementAction
{
    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Unsuspend');
    }

    /**
     * @inheritdoc
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        // Get the users that are suspended
        $query->status(User::STATUS_SUSPENDED);
        /** @var User[] $users */
        $users = $query->all();
        $currentUser = Craft::$app->getUser()->getIdentity();

        $successCount = count(array_filter($users, function(User $user) use($currentUser) {
            try {
                return Craft::$app->getUsers()->unsuspendUser($user, $currentUser);
            } catch (\Throwable $e) {
                return false;
            }
        }));

        if ($successCount !== count($users)) {
            $this->setMessage(Craft::t('app', 'Could not unsuspend all users.'));
            return false;
        }

        $this->setMessage(Craft::t('app', 'Users unsuspended.'));
        return true;
    }
}
