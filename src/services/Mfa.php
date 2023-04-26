<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\auth\type\EmailCode;
use craft\auth\type\GoogleAuthenticator;
use craft\auth\type\WebAuthn;
use craft\base\mfa\BaseMfaType;
use craft\elements\User;
use craft\events\MfaTypeEvent;
use yii\base\Component;
use yii\base\Exception;

/**
 * Mfa service.
 * An instance of the Mfa service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getMfa()|`Craft::$app->mfa`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.5.0
 */
class Mfa extends Component
{
    public const EVENT_REGISTER_MFA_TYPES = 'registerMfaTypes';

    /**
     * @var string the session key used to store the id of the user we're logging in.
     */
    protected const MFA_USER_SESSION_KEY = 'craft.mfa.user';

    /**
     * @var BaseMfaType|null MFA Type instance in use
     */
    private ?BaseMfaType $_mfaType = null;

    /**
     * @var array $_mfaTypes all available MFA types
     */
    private array $_mfaTypes = [];


//    public function mfaEnabled(User $user): bool
//    {
//        return $user->requireMfa && $this->_getStoredSecret($user->id) !== null;
//    }

    /**
     * Store the user id and duration in session while we proceed to the MFA step of logging them in
     *
     * @param User $user
     * @param int $duration
     * @return void
     * @throws \craft\errors\MissingComponentException
     */
    public function storeDataForMfaLogin(User $user, int $duration): void
    {
        Craft::$app->getSession()->set(self::MFA_USER_SESSION_KEY, [$user->id, $duration]);
    }

    /**
     * Get user and duration data from session
     *
     * @return array|null
     * @throws Exception
     * @throws \craft\errors\MissingComponentException
     */
    public function getMfaDataFromSession(): ?array
    {
        $data = Craft::$app->getSession()->get(self::MFA_USER_SESSION_KEY);

        if ($data === null) {
            return null;
        }

        if (is_array($data)) {
            [$userId, $duration] = $data;
            $user = User::findOne(['id' => $userId]);

            if ($user === null) {
                throw new Exception(Craft::t('app', 'Something went wrong. Please start again.'));
            }

            return compact('user', 'duration');
        }

        return null;
    }

    /**
     * Get the user we're logging in via MFA from session
     *
     * @return User|null
     * @throws Exception
     * @throws \craft\errors\MissingComponentException
     */
    public function getMfaUserFromSession(): ?User
    {
        $data = $this->getMfaDataFromSession();

        return $data['user'] ?? null;
    }

    /**
     * Remove user's data from session
     *
     * @return void
     * @throws \craft\errors\MissingComponentException
     */
    public function removeMfaDataFromSession(): void
    {
        Craft::$app->getSession()->remove(self::MFA_USER_SESSION_KEY);
    }

    /**
     * Get html of the form for the MFA step
     *
     * @return string
     */
    public function getInputHtml(): string
    {
        $user = $this->getUserForMfa();

        if ($user === null) {
            return '';
        }

        $this->_mfaType = $user->getDefaultMfaType();

        return $this->_mfaType->getInputHtml();
    }

    /**
     * Verify MFA step
     *
     * @param array $mfaFields
     * @param string $currentMethod
     * @return bool
     */
    public function verify(array $mfaFields, string $currentMethod): bool
    {
        $user = $this->getUserForMfa();

        if ($user === null) {
            return false;
        }

        if (empty($currentMethod)) {
            throw new Exception('MFA method not specified.');
        }

        $mfaType = new $currentMethod();

        if (!($mfaType instanceof BaseMfaType)) {
            throw new Exception('MFA Type needs to be an instance of ' . BaseMfaType::class);
        }

        $this->_mfaType = new $mfaType();

        return $this->_mfaType->verify($mfaFields);
    }

    /**
     * Returns a list of all available MFA types except the one passed in as current
     *
     * @param ?string $currentMethod
     * @return array
     */
    public function getAlternativeMfaTypes(?string $currentMethod = null): array
    {
        return array_filter($this->getAllMfaTypes(), function($type) use ($currentMethod) {
            return $type !== $currentMethod && $type !== WebAuthn::class;
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Returns a list of all available MFA types
     *
     * @return array
     */
    public function getAllMfaTypes(bool $withConfig = false): array
    {
        if (!empty($this->_mfaTypes)) {
            $types = $this->_mfaTypes;
        } else {
            $types = [
                GoogleAuthenticator::class => [
                    'name' => GoogleAuthenticator::displayName(),
                    'description' => GoogleAuthenticator::getDescription(),
                    'config' => [
                        'requiresSetup' => GoogleAuthenticator::$requiresSetup,
                    ],
                ],
                EmailCode::class => [
                    'name' => EmailCode::displayName(),
                    'description' => EmailCode::getDescription(),
                    'config' => [
                        'requiresSetup' => EmailCode::$requiresSetup,
                    ],
                ],
                WebAuthn::class => [
                    'name' => WebAuthn::displayName(),
                    'description' => WebAuthn::getDescription(),
                    'config' => [
                        'requiresSetup' => WebAuthn::$requiresSetup,
                    ],
                ],
            ];
        }

        $event = new MfaTypeEvent([
            'types' => $types,
        ]);

        $this->trigger(self::EVENT_REGISTER_MFA_TYPES, $event);

        $this->_mfaTypes = $event->types;

        if (!$withConfig) {
            foreach ($event->types as $key => $types) {
                unset($event->types[$key]['config']);
            }
        }

        return $event->types;
    }

    /**
     * Get user for MFA login.
     * First try to get the logged in user (used e.g. when changing a setup).
     * Then try to get one from the session.
     *
     * @return User|null
     * @throws \Throwable
     * @throws \craft\errors\MissingComponentException
     * @throws \yii\base\Exception
     */
    public function getUserForMfa(): ?User
    {
        // first let's check if user is logged in; if yes, this is run via a setup action from their profile
        $user = Craft::$app->getUser()->getIdentity();

        if ($user === null) {
            // then try to get data from session
            $user = Craft::$app->getMfa()->getMfaUserFromSession();
        }

        return $user;
    }
}
