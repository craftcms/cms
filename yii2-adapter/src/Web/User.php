<?php

namespace CraftCms\Yii2Adapter\Web;

use CraftCms\Yii2Adapter\IdentityWrapper;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use yii\db\BaseActiveRecord;
use yii\web\IdentityInterface;

class User extends \yii\web\User
{
    private IdentityInterface|false|null $_identity = false;

    private ?AuthManager $_illuminateAuthManager = null;

    /**
     * {@inheritdoc}
     */
    public function getIdentity($autoRenew = true)
    {
        if ($this->_identity !== false) {
            return $this->_identity;
        }

        $identity = $this->getIlluminateAuthManager()->user();

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
        parent::setIdentity($identity);

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
            $this->getIlluminateAuthManager()->logout();

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
        $this->getIlluminateAuthManager()->loginUsingId($id, remember: $duration > 3600);
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
