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
use craft\helpers\Json;
use yii\base\Exception;

/**
 * DeleteUsers represents a Delete Users element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class DeleteUsers extends ElementAction
{
    /**
     * @var int|null The user ID that the deleted user’s content should be transferred to
     */
    public $transferContentTo;

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Delete…');
    }

    /**
     * @inheritdoc
     */
    public static function isDestructive(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml()
    {
        $type = Json::encode(static::class);
        $undeletableIds = Json::encode($this->_getUndeletableUserIds());
        $redirect = Json::encode(Craft::$app->getSecurity()->hashData(Craft::$app->getEdition() === Craft::Pro ? 'users' : 'dashboard'));

        $js = <<<JS
(function()
{
    var trigger = new Craft.ElementActionTrigger({
        type: {$type},
        batch: true,
        validateSelection: function(\$selectedItems)
        {
            for (var i = 0; i < \$selectedItems.length; i++)
            {
                if ($.inArray(\$selectedItems.eq(i).find('.element').data('id').toString(), $undeletableIds) != -1)
                {
                    return false;
                }
            }

            return true;
        },
        activate: function(\$selectedItems)
        {
            Craft.elementIndex.setIndexBusy();
            var ids = Craft.elementIndex.getSelectedElementIds();
            Craft.postActionRequest('users/user-content-summary', {userId: ids}, function(response, textStatus) {
                Craft.elementIndex.setIndexAvailable();
                if (textStatus === 'success') {
                    var modal = new Craft.DeleteUserModal(ids, {
                        contentSummary: response,
                        onSubmit: function()
                        {
                            Craft.elementIndex.submitAction({$type}, Garnish.getPostData(modal.\$container));
                            modal.hide();
        
                            return false;
                        },
                        redirect: {$redirect}
                    });                    
                }
            });
        }
    });
})();
JS;

        Craft::$app->getView()->registerJs($js);
    }

    /**
     * @inheritdoc
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        /** @var User[] $users */
        $users = $query->all();
        $undeletableIds = $this->_getUndeletableUserIds();

        // Are we transferring the user's content to a different user?
        if (is_array($this->transferContentTo) && isset($this->transferContentTo[0])) {
            $this->transferContentTo = $this->transferContentTo[0];
        }

        if (!empty($this->transferContentTo)) {
            $transferContentTo = Craft::$app->getUsers()->getUserById($this->transferContentTo);

            if (!$transferContentTo) {
                throw new Exception("No user exists with the ID “{$this->transferContentTo}”");
            }
        } else {
            $transferContentTo = null;
        }

        // Delete the users
        $elementsService = Craft::$app->getElements();
        foreach ($users as $user) {
            if (!in_array($user->id, $undeletableIds, false)) {
                $user->inheritorOnDelete = $transferContentTo;
                $elementsService->deleteElement($user);
            }
        }

        $this->setMessage(Craft::t('app', 'Users deleted.'));

        return true;
    }

    /**
     * Returns a list of the user IDs that can't be deleted.
     *
     * @return array
     */
    private function _getUndeletableUserIds(): array
    {
        if (!Craft::$app->getUser()->getIsAdmin()) {
            // Only admins can delete other admins
            return User::find()->admin()->ids();
        }

        // Can't delete your own account from here
        return [Craft::$app->getUser()->getIdentity()->id];
    }
}
