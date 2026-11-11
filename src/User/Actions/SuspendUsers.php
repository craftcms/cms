<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Actions;

use CraftCms\Cms\Element\Actions\ElementAction;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\UserQuery;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Elements\User;
use Override;
use Throwable;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class SuspendUsers extends ElementAction
{
    #[Override]
    public function getTriggerLabel(): string
    {
        return t('Suspend');
    }

    #[Override]
    public function performAction(ElementQueryInterface $query): bool
    {
        // Get the users that aren't already suspended
        $query->status = UserQuery::STATUS_CREDENTIALED;

        /** @var User[] $users */
        $users = $query->all();
        $currentUser = currentUser();

        if (! $currentUser instanceof CraftUser) {
            return false;
        }

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
