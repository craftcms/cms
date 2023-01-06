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
use Throwable;

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
    public function getTriggerHtml(): ?string
    {
        Craft::$app->getView()->registerJsWithVars(function($type) {
            return <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        bulk: true,
        validateSelection: \$selectedItems => {
            for (let i = 0; i < \$selectedItems.length; i++) {
                const \$element = \$selectedItems.eq(i).find('.element');
                if (
                    !Garnish.hasAttr(\$element, 'data-can-suspend') ||
                    !Garnish.hasAttr(\$element, 'data-suspended')
                ) {
                    return false;
                }
            }

            return true;
        }
    });
})();
JS;
        }, [
            static::class,
        ]);

        return null;
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
        $usersService = Craft::$app->getUsers();
        $currentUser = Craft::$app->getUser()->getIdentity();

        $successCount = count(array_filter($users, function(User $user) use ($usersService, $currentUser) {
            try {
                return $usersService->canSuspend($currentUser, $user) && $usersService->unsuspendUser($user);
            } catch (Throwable) {
                return false;
            }
        }));

        if ($successCount !== count($users)) {
            $this->setMessage(Craft::t('app', 'Couldn’t unsuspend all users.'));
            return false;
        }

        $this->setMessage(Craft::t('app', 'Users unsuspended.'));
        return true;
    }
}
