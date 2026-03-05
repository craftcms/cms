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
use craft\elements\db\UserQuery;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
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
        HtmlStack::jsWithVars(fn($type, $userId) => <<<JS
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
    })
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
        $currentUser = Auth::user();

        $successCount = count(array_filter($users, function(User $user) use ($currentUser) {
            try {
                if (!Users::canSuspend($currentUser, $user)) {
                    return false;
                }
                Users::suspendUser($user);
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
