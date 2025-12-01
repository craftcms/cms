<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\UserQuery;
use craft\elements\User;
use Throwable;
use function CraftCms\Cms\t;

/**
 * SuspendUsers represents a Suspend Users element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class SuspendUsers extends ElementAction
{
    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return t('Suspend');
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml(): ?string
    {
        Craft::$app->getView()->registerJsWithVars(fn($type, $userId) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        bulk: true,
        validateSelection: (selectedItems, elementIndex) => {
            for (let i = 0; i < selectedItems.length; i++) {
                const \$element = selectedItems.eq(i).find('.element');
                if (
                    !Garnish.hasAttr(\$element, 'data-can-suspend') ||
                    Garnish.hasAttr(\$element, 'data-suspended') ||
                    \$element.data('id') == $userId
                ) {
                    return false;
                }
            }

            return true;
        }
    });
})();
JS, [
            static::class,
            Craft::$app->getUser()->getId(),
        ]);

        return null;
    }

    /**
     * @inheritdoc
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        /** @var ElementQuery $query */
        // Get the users that aren't already suspended
        $query->status = UserQuery::STATUS_CREDENTIALED;

        /** @var User[] $users */
        $users = $query->all();
        $usersService = Craft::$app->getUsers();
        $currentUser = Craft::$app->getUser()->getIdentity();

        $successCount = count(array_filter($users, function(User $user) use ($usersService, $currentUser) {
            try {
                if (!$usersService->canSuspend($currentUser, $user)) {
                    return false;
                }
                $usersService->suspendUser($user);
                return true;
            } catch (Throwable) {
                return false;
            }
        }));

        if ($successCount !== count($users)) {
            $this->setMessage(t('Couldn’t suspend all users.'));
            return false;
        }

        $this->setMessage(t('Users suspended.'));
        return true;
    }
}
