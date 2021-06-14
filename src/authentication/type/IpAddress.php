<?php
declare(strict_types=1);

namespace craft\authentication\type;

use Craft;
use craft\authentication\base\Type;
use craft\elements\User;
use craft\models\authentication\State;

/**
 * This step type checks if the IP address matches the defined rules.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 *
 * @property-read string $inputFieldHtml
 */
class IpAddress extends Type
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
    public static function displayName(): string
    {
        return Craft::t('app', 'IP address filter');
    }

    /**
     * @inheritdoc
     */
    public static function getDescription(): string
    {
        return Craft::t('app', 'Filter by user IP address');
    }

    /**
     * @inheritdoc
     */
    public function authenticate(array $credentials, User $user = null): State
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

    /**
     * No fields for this step.
     *
     * @return string
     */
    public function getInputFieldHtml(): string
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function getRequiresInput(): bool
    {
        return false;
    }
}
