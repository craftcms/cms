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
use craft\elements\User;
use craft\helpers\Json;

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
        return Craft::t('app', 'Suspend');
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml()
    {
        $type = Json::encode(static::class);
        $userId = Json::encode(Craft::$app->getUser()->getIdentity()->id);

        $js = <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: {$type},
        batch: true,
        validateSelection: function(\$selectedItems)
        {
            for (var i = 0; i < \$selectedItems.length; i++)
            {
                if (\$selectedItems.eq(i).find('.element').data('id') == {$userId})
                {
                    return false;
                }
            }

            return true;
        }
    });
})();
JS;

        Craft::$app->getView()->registerJs($js);
        return null;
    }

    /**
     * @inheritdoc
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        /** @var ElementQuery $query */
        // Get the users that aren't already suspended
        $query->status = [
            User::STATUS_ACTIVE,
            User::STATUS_PENDING,
        ];

        /** @var User[] $users */
        $users = $query->all();
        $currentUser = Craft::$app->getUser()->getIdentity();

        $successCount = count(array_filter($users, function(User $user) use($currentUser) {
            try {
                return Craft::$app->getUsers()->suspendUser($user, $currentUser);
            } catch (\Throwable $e) {
                return false;
            }
        }));

        if ($successCount !== count($users)) {
            $this->setMessage(Craft::t('app', 'Could not suspend all users.'));
            return false;
        }

        $this->setMessage(Craft::t('app', 'Users Suspended.'));
        return true;
    }
}
