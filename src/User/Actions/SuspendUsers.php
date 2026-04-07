<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Actions;

use Craft;
use CraftCms\Cms\Element\Actions\ElementAction;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\UserQuery;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use Throwable;

use function CraftCms\Cms\t;

class SuspendUsers extends ElementAction
{
    #[\Override]
    public function getTriggerLabel(): string
    {
        return t('Suspend');
    }

    public function getTriggerHtml(): ?string
    {
        HtmlStack::jsWithVars(fn ($type, $userId) => <<<JS
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

    #[\Override]
    public function performAction(ElementQueryInterface $query): bool
    {
        // Get the users that aren't already suspended
        $query->status = UserQuery::STATUS_CREDENTIALED;

        /** @var User[] $users */
        $users = $query->all();
        $currentUser = Auth::user();

        $successCount = count(array_filter($users, function (User $user) use ($currentUser) {
            try {
                if (! Users::canSuspend($currentUser, $user)) {
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
