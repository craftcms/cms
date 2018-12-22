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
 * @since 3.0
 */
class SuspendUsers extends ElementAction
{
    // Public Methods
    // =========================================================================

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

        $js = <<<EOD
(function()
{
    var trigger = new Craft.ElementActionTrigger({
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
EOD;

        Craft::$app->getView()->registerJs($js);
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
            User::STATUS_LOCKED,
            User::STATUS_PENDING,
        ];

        /** @var User[] $users */
        $users = $query->all();

        foreach ($users as $user) {
            if (!$user->getIsCurrent()) {
                Craft::$app->getUsers()->suspendUser($user);
            }
        }

        $this->setMessage(Craft::t('app', 'Users suspended.'));

        return true;
    }
}
