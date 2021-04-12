<?php
declare(strict_types=1);

namespace craft\authentication\step;

use Craft;
use craft\authentication\base\Step;
use craft\elements\User;
use craft\models\AuthenticationState;

class IpAddress extends Step
{
    /**
     * @var string[] A list of allowed IP addresses, if `permissive` is set to `false`
     */
    public array $allowed = [];

    /**
     * @var string[] A list of denied IP addresses, if `permissive` is set to `true`.
     */
    public array $denied = [];

    /**
     * @var bool If set to `true`, will check the IP address against the `denied` list, otherwise will check whether the
     * IP address is in the `allowed` list. Defaults to `false`.
     */
    public bool $permissive = false;

    /**
     * @inheritdoc
     */
    public function authenticate(array $credentials, User $user = null): AuthenticationState
    {
        $ip = Craft::$app->getRequest()->getUserIP();

        if (!$this->isAllowedIP($ip)) {
            Craft::warning("Authentication denied for IP `$ip`");
            return $this->state;
        }

        return $this->completeStep($user);
    }

    /**
     * Check whether a given IP address is allowed or not.
     *
     * @param string|null $address
     * @return bool
     */
    protected function isAllowedIP(?string $address): bool
    {
        if (is_null($address)) {
            return false;
        }
        if ($this->permissive) {
            return !in_array($address, $this->denied, true);
        }

        return in_array($address, $this->allowed, true);
    }
}
