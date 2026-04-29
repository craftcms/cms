<?php

namespace craft\elements\actions;

use craft\elements\User;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Actions\ElementAction;
use CraftCms\Cms\Element\Contracts\DeleteActionInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\Users;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Override;
use RuntimeException;

use function CraftCms\Cms\t;

/**
 * DeleteUsers represents a Delete Users element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 5.10.0
 */
class DeleteUsers extends ElementAction implements DeleteActionInterface
{
    public int|array|null $transferContentTo = null;

    public bool $hard = false;

    public function canHardDelete(): bool
    {
        return true;
    }

    public function setHardDelete(): void
    {
        $this->hard = true;
    }

    #[Override]
    public function getTriggerLabel(): string
    {
        if ($this->hard) {
            return t('Delete permanently');
        }

        return t('Delete…');
    }

    #[Override]
    public static function isDestructive(): bool
    {
        return true;
    }

    public function getTriggerHtml(): ?string
    {
        if ($this->hard) {
            return '<div class="btn formsubmit">' . $this->getTriggerLabel() . '</div>';
        }

        HtmlStack::jsWithVars(
            fn($type, $redirect) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        bulk: true,
        validateSelection: (selectedItems, elementIndex) => {
            for (let i = 0; i < selectedItems.length; i++) {
                if (!Garnish.hasAttr(selectedItems.eq(i).find('.element'), 'data-deletable')) {
                    return false;
                }
            }
            return true;
        },
        activate: (selectedItems, elementIndex) => {
            elementIndex.setIndexBusy();
            const ids = elementIndex.getSelectedElementIds();
            const data = {userId: ids};
            Craft.sendActionRequest('POST', 'users/user-content-summary', {data})
                .then((response) => {
                    const modal = new Craft.DeleteUserModal(ids, {
                        contentSummary: response.data,
                        onSubmit: () => {
                            elementIndex.submitAction($type, Garnish.getPostData(modal.\$container))
                            modal.hide();
                            return false;
                        },
                        redirect: $redirect
                    })
                })
                .finally(() => {
                    elementIndex.setIndexAvailable();
                });
        },
    })
})();
JS,
            [
                static::class,
                Crypt::encrypt(Edition::get() === Edition::Solo ? 'dashboard' : 'users'),
            ]);

        return null;
    }

    public function getConfirmationMessage(): ?string
    {
        if ($this->hard) {
            return t('Are you sure you want to permanently delete the selected {type}?', [
                'type' => User::pluralLowerDisplayName(),
            ]);
        }

        return null;
    }

    #[Override]
    public function performAction(ElementQueryInterface $query): bool
    {
        /** @var User[] $users */
        $users = $query->all();

        // Are we transferring the user’s content to a different user?
        if (is_array($this->transferContentTo)) {
            $this->transferContentTo = reset($this->transferContentTo) ?: null;
        }

        if ($this->transferContentTo) {
            $transferContentTo = Users::getUserById((int) $this->transferContentTo);

            if (!$transferContentTo) {
                throw new RuntimeException("No user exists with the ID “{$this->transferContentTo}”");
            }
        } else {
            $transferContentTo = null;
        }

        // Delete the users
        $deletedCount = 0;

        foreach ($users as $user) {
            if (Gate::check('delete', $user)) {
                /** @phpstan-ignore-next-line */
                $user->inheritorOnDelete = $transferContentTo;
                if (Elements::deleteElement($user, $this->hard)) {
                    $deletedCount++;
                }
            }
        }

        if ($deletedCount !== count($users)) {
            if ($deletedCount === 0) {
                $this->setMessage(t('Couldn’t delete {type}.', [
                    'type' => User::pluralLowerDisplayName(),
                ]));
            } else {
                $this->setMessage(t('Couldn’t delete all {type}.', [
                    'type' => User::pluralLowerDisplayName(),
                ]));
            }

            return false;
        }

        $this->setMessage(t('{type} deleted.', [
            'type' => User::pluralDisplayName(),
        ]));

        return true;
    }
}
