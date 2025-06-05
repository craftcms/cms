<?php

namespace Craft\Cms\Yii\Web;

use Illuminate\Auth\AuthManager;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use yii\db\BaseActiveRecord;
use yii\web\IdentityInterface;

class User extends \yii\web\User
{
    /**
     * @var \yii\web\IdentityInterface|bool user identity.
     */
    private $_identity = false;

    /**
     * @var \Illuminate\Auth\AuthManager related Laravel auth manager.
     */
    private $_illuminateAuthManager;

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

    /**
     * @return \Illuminate\Auth\AuthManager
     */
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
            $id = $identity->id;
        }

        $this->getIlluminateAuthManager()->loginUsingId($id);
    }

    /**
     * Converts Laravel identity into Yii one.
     *
     * @param  mixed  $identity Laravel identity.
     * @return IdentityInterface Yii compatible identity instance.
     */
    protected function convertIlluminateIdentity($identity): IdentityInterface
    {
        [$id, $attributes] = match(true) {
            $identity instanceof Model => [$identity->getKey(), $identity->getAttributes()],
            $identity instanceof Authenticatable => [$identity->getAuthIdentifier(), []],
            is_array($identity) && isset($identity['id']) => [$identity['id'], $identity],
            default => throw new RuntimeException('Unable to convert identity from "'.print_r($identity, true).'"'),
        };

        $identityClass = $this->identityClass;

        if (! empty($attributes) && is_subclass_of($identityClass, BaseActiveRecord::class)) {
            $record = new $identityClass;

            call_user_func([$identityClass, 'populateRecord'], $record, $attributes);

            return $record;
        }

        return call_user_func([$identityClass, 'findIdentity'], $id);
    }
}
