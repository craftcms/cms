<?php

namespace CraftCms\Yii2Adapter\Web;

use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Yii2Adapter\IdentityWrapper;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use yii\db\BaseActiveRecord;
use yii\web\IdentityInterface;

use function CraftCms\Cms\craftAuth;

class User extends \yii\web\User
{
    private IdentityInterface|false|null $_identity = false;

    /**
     * The user [[setIdentity()]] put on the Craft guard, which clearing the identity takes off while it's still there.
     */
    private ?UserElement $_guardUser = null;

    private ?AuthManager $_illuminateAuthManager = null;

    /**
     * {@inheritdoc}
     */
    public function getIdentity($autoRenew = true)
    {
        if ($this->_identity !== false) {
            return $this->_identity;
        }

        $identity = craftAuth()->user();

        if ($identity !== null) {
            $identity = $this->convertIlluminateIdentity($identity);
        }

        $this->_identity = $identity;

        return $this->_identity;
    }

    /**
     * {@inheritdoc}
     */
    public function setIdentity($identity): void
    {
        $user = $identity;

        if ($identity instanceof UserElement && !$identity instanceof IdentityInterface) {
            $identity = new IdentityWrapper($identity);
        }

        parent::setIdentity($identity);

        if ($user instanceof UserElement) {
            // Setting the guard's user fires `Authenticated`, whose adapter listener comes back here.
            if (craftAuth()->id() !== $user->getAuthIdentifier()) {
                craftAuth()->setUser($user);
                $this->_guardUser = $user;
            }
        } elseif ($identity === null && $this->_guardUser !== null) {
            $guardUser = $this->_guardUser;
            $this->_guardUser = null;

            if (craftAuth()->hasUser() && craftAuth()->user() === $guardUser) {
                // Go back to the session's user, if there is one, the next time the identity is read.
                craftAuth()->forgetUser();
                $this->_identity = false;

                return;
            }
        }

        $this->_identity = $identity;
    }

    public function getIlluminateAuthManager(): AuthManager
    {
        return $this->_illuminateAuthManager ??= app('auth');
    }

    /**
     * {@inheritdoc}
     */
    public function switchIdentity($identity, $duration = 0): void
    {
        $this->setIdentity($identity);

        if ($identity === null) {
            craftAuth()->logout();

            return;
        }

        if ($identity instanceof BaseActiveRecord) {
            $id = $identity->getPrimaryKey();
        } else {
            $id = $identity->getId();
        }

        /**
         * When "Remember me for 2 weeks" is checked, the duration will be larger
         * than 3600, so we pass remember to Laravel's auth as well.
         */
        craftAuth()->loginUsingId($id, remember: $duration > 3600);
    }

    protected function convertIlluminateIdentity(mixed $identity): IdentityInterface
    {
        [$id, $attributes] = match (true) {
            $identity instanceof Model => [$identity->getKey(), $identity->getAttributes()],
            $identity instanceof Authenticatable => [$identity->getAuthIdentifier(), []],
            is_array($identity) && isset($identity['id']) => [$identity['id'], $identity],
            default => throw new RuntimeException('Unable to convert identity from "' . print_r($identity, true) . '"'),
        };

        $identity = \CraftCms\Cms\User\Elements\User::find()->id($id)->status(null)->firstOrFail();

        return new IdentityWrapper($identity);
    }
}
