<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\elements\actions;

use Craft;
use craft\app\base\ElementAction;
use craft\app\elements\db\ElementQueryInterface;
use craft\app\elements\User;
use craft\app\helpers\Json;
use yii\base\Exception;

/**
 * DeleteUsers represents a Delete Users element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class DeleteUsers extends ElementAction
{
    // Properties
    // =========================================================================

    /**
     * @var integer The user ID that the deleted user’s content should be transferred to
     */
    public $transferContentTo;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTriggerLabel()
    {
        return Craft::t('app', 'Delete…');
    }

    /**
     * @inheritdoc
     */
    public static function isDestructive()
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
        $redirect = Json::encode(Craft::$app->getSecurity()->hashData(Craft::$app->getEdition() == Craft::Pro ? 'users' : 'dashboard'));

        $js = <<<EOT
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
			var modal = new Craft.DeleteUserModal(Craft.elementIndex.getSelectedElementIds(), {
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
})();
EOT;

        Craft::$app->getView()->registerJs($js);
    }

    /**
     * @inheritdoc
     */
    public function performAction(ElementQueryInterface $query)
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
        foreach ($users as $user) {
            if (!in_array($user->id, $undeletableIds)) {
                Craft::$app->getUsers()->deleteUser($user, $transferContentTo);
            }
        }

        $this->setMessage(Craft::t('app', 'Users deleted.'));

        return true;
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns a list of the user IDs that can't be deleted.
     *
     * @return array
     */
    private function _getUndeletableUserIds()
    {
        if (!Craft::$app->getUser()->getIsAdmin()) {
            // Only admins can delete other admins
            return User::find()->admin()->ids();
        }

        // Can't delete your own account from here
        return [Craft::$app->getUser()->getIdentity()->id];
    }
}
