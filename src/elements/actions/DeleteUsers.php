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
use yii\base\Exception;

/**
 * DeleteUsers represents a Delete Users element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class DeleteUsers extends ElementAction implements DeleteActionInterface
{
    /**
     * @var int|int[]|null The user ID that the deleted user’s content should be transferred to
     */
    public int|array|null $transferContentTo = null;

    /**
     * @var bool Whether to permanently delete the elements.
     * @since 3.6.5
     */
    public bool $hard = false;

    /**
     * @inheritdoc
     */
    public function canHardDelete(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function setHardDelete(): void
    {
        $this->hard = true;
    }

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        if ($this->hard) {
            return Craft::t('app', 'Delete permanently');
        }

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
    public function getTriggerHtml(): ?string
    {
        if ($this->hard) {
            return '<div class="btn formsubmit">' . $this->getTriggerLabel() . '</div>';
        }

        Craft::$app->getView()->registerJsWithVars(
            fn($type, $undeletableIds, $redirect) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        bulk: true,
        validateSelection: \$selectedItems => {
            for (let i = 0; i < \$selectedItems.length; i++) {
                if ($.inArray(\$selectedItems.eq(i).find('.element').data('id').toString(), $undeletableIds) != -1) {
                    return false;
                }
            }
            return true;
        },
        activate: () => {
            Craft.elementIndex.setIndexBusy();
            const ids = Craft.elementIndex.getSelectedElementIds();
            const data = {userId: ids};
            Craft.sendActionRequest('POST', 'users/user-content-summary', {data})
                .then((response) => {
                    const modal = new Craft.DeleteUserModal(ids, {
                        contentSummary: response.data,
                        onSubmit: () => {
                            Craft.elementIndex.submitAction($type, Garnish.getPostData(modal.\$container));
                            modal.hide();
                            return false;
                        },
                        redirect: $redirect
                    });                    
                })
                .finally(() => {
                    Craft.elementIndex.setIndexAvailable();
                });
        },
    });
})();
JS,
            [
                static::class,
                $this->_getUndeletableUserIds(),
                Craft::$app->getSecurity()->hashData(Craft::$app->getEdition() === Craft::Pro ? 'users' : 'dashboard'),
            ]);

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getConfirmationMessage(): ?string
    {
        if ($this->hard) {
            return Craft::t('app', 'Are you sure you want to permanently delete the selected {type}?', [
                'type' => User::pluralLowerDisplayName(),
            ]);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        /** @var User[] $users */
        $users = $query->all();
        $undeletableIds = $this->_getUndeletableUserIds();

        // Are we transferring the user’s content to a different user?
        if (is_array($this->transferContentTo)) {
            $this->transferContentTo = reset($this->transferContentTo) ?: null;
        }

        if ($this->transferContentTo) {
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
                $elementsService->deleteElement($user, $this->hard);
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
