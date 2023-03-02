<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\mfa\BaseMfaType;
use craft\elements\User;
use craft\events\MfaOptionEvent;
use craft\helpers\UrlHelper;
use craft\mfa\type\EmailCode;
use craft\mfa\type\GoogleAuthenticator;
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
    public const EVENT_REGISTER_MFA_OPTIONS = 'registerMfaOptions';

    /**
     * @var string the session key used to store the id of the user we're logging in.
     */
    protected const MFA_USER_SESSION_KEY = 'craft.mfa.user';

    /**
     * @var BaseMfaType|null authenticator instance in use
     */
    private ?BaseMfaType $_authenticator = null;

    /**
     * @var array $_mfaOptions all available MFA options
     */
    private array $_mfaOptions = [];


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
    public function getDataForMfaLogin(): ?array
    {
        $data = Craft::$app->getSession()->get(self::MFA_USER_SESSION_KEY);

        if ($data === null) {
            return null;
        }

        if (is_array($data)) {
            [$userId, $duration] = $data;
            $user = User::findOne(['id' => $userId]);

            if ($user === null) {
                throw new Exception(Craft::t('app', 'Can`t find the user.'));
            }

            return compact('user', 'duration');
        }

        return null;
    }

    /**
     * Get the user we're logging in via MFA from session
     * @return User|null
     * @throws Exception
     * @throws \craft\errors\MissingComponentException
     */
    public function getUserForMfaLogin(): ?User
    {
        $data = $this->getDataForMfaLogin();

        return $data['user'] ?? null;
    }

    /**
     * Remove user's data from session
     *
     * @return void
     * @throws \craft\errors\MissingComponentException
     */
    public function removeDataForMfaLogin(): void
    {
        Craft::$app->getSession()->remove(self::MFA_USER_SESSION_KEY);
    }

    /**
     * Get MFA step URL - used for non-ajax requests only?
     *
     * @param ?string $default
     * @return string
     */
    public function getMfaUrl(string $default = null): string
    {
        if ($default !== null) {
            $url = UrlHelper::cpUrl($default);
        } else {
            $url = UrlHelper::cpUrl('mfa');
        }
        //Craft::$app->getConfig()->getGeneral()->mfaUrl
        // Strip out any tags that may have gotten in there by accident
        // i.e. if there was a {siteUrl} tag in the Site URL setting, but no matching environment variable,
        // so they ended up on something like http://example.com/%7BsiteUrl%7D/some/path
        return str_replace(['{', '}'], '', $url);
    }

    /**
     * Get html of the form for the MFA step
     *
     * @return string
     */
    public function getInputHtml(): string
    {
        $user = $this->getUserForMfaLogin();
        if ($user === null) {
            return '';
        }

        $this->_authenticator = $user->getDefaultMfaOption();

        return $this->_authenticator->getInputHtml();
    }

    /**
     * Verify MFA step
     *
     * @param array $mfaFields
     * @param string|null $currentMethod
     * @return bool
     */
    public function verify(array $mfaFields, ?string $currentMethod = ''): bool
    {
        $user = $this->getUserForMfaLogin();

        if ($user === null) {
            return false;
        }

        if ($this->_authenticator === null) {
            $this->_authenticator = $user->getDefaultMfaOption();
        }

        $newAuthenticator = new $currentMethod();
        if (!empty($currentMethod) && $newAuthenticator instanceof BaseMfaType) {
            $this->_authenticator = new $newAuthenticator();
        }

        return $this->_authenticator->verify($mfaFields);
    }

    /**
     * Returns a list of all available MFA options except the one passed in as current
     *
     * @param string $currentAuthenticator
     * @return array
     */
    public function getAlternativeMfaOptions(string $currentAuthenticator = ''): array
    {
        return array_filter($this->getAllMfaOptions(), function($option) use ($currentAuthenticator) {
            return $option !== $currentAuthenticator;
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Returns a list of all available MFA options
     *
     * @return array
     */
    public function getAllMfaOptions(bool $withConfig = false): array
    {
        if (!empty($this->_mfaOptions)) {
            $options = $this->_mfaOptions;
        } else {
            $options = [
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
            ];
        }

        $event = new MfaOptionEvent([
            'options' => $options,
        ]);

        $this->trigger(self::EVENT_REGISTER_MFA_OPTIONS, $event);

        $this->_mfaOptions = $event->options;

        if (!$withConfig) {
            foreach ($event->options as $key => $option) {
                unset($event->options[$key]['config']);
            }
        }

        return $event->options;
    }
}
