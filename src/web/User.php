<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\controllers\UsersController;
use craft\db\Table;
use craft\elements\User as UserElement;
use craft\errors\UserLockedException;
use craft\events\LoginFailureEvent;
use craft\helpers\ConfigHelper;
use craft\helpers\Db;
use craft\helpers\Session as SessionHelper;
use craft\helpers\UrlHelper;
use craft\helpers\User as UserHelper;
use craft\validators\UserPasswordValidator;
use yii\web\Cookie;
use yii\web\ForbiddenHttpException;
use yii\web\IdentityInterface;

/**
 * The User component provides APIs for managing the user authentication status.
 *
 * An instance of the User component is globally accessible in Craft via [[\yii\web\Application::getUser()|`Craft::$app->user`]].
 *
 * @property bool $hasElevatedSession Whether the user currently has an elevated session
 * @property UserElement|null $identity The logged-in user.
 * @method UserElement|null getIdentity(bool $autoRenew = true) Returns the logged-in user.
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class User extends \yii\web\User
{
    /**
     * @var string The session variable name used to store the duration of the authenticated state.
     * @since 3.6.8
     */
    public string $authDurationParam = '__duration';

    /**
     * @var string the session variable name used to store the user session token.
     */
    public string $tokenParam = '__token';

    /**
     * @var array The configuration of the username cookie.
     * @see Cookie
     */
    public array $usernameCookie;

    /**
     * @var string The session variable name used to store the value of the expiration timestamp of the elevated session state.
     */
    public string $elevatedSessionTimeoutParam = '__elevated_timeout';

    // Authentication
    // -------------------------------------------------------------------------

    /**
     * Logs in a user by their ID.
     *
     * @param int $userId The user’s ID
     * @param int $duration The number of seconds that the user can remain in logged-in status.
     * Defaults to 0, meaning login till the user closes the browser or the session is manually destroyed.
     * If greater than 0 and [[enableAutoLogin]] is true, cookie-based login will be supported.
     * Note that if [[enableSession]] is false, this parameter will be ignored.
     * @return bool Whether the user is logged in
     */
    public function loginByUserId(int $userId, int $duration = 0): bool
    {
        $user = Craft::$app->getUsers()->getUserById($userId);

        if (!$user) {
            return false;
        }

        return $this->login($user, $duration);
    }

    /**
     * Sends a username cookie.
     *
     * This method is used after a user is logged in. It saves the logged-in user’s username in a cookie,
     * so that login forms can remember the initial Username value on login forms.
     *
     * @param UserElement $user
     * @see afterLogin()
     */
    public function sendUsernameCookie(UserElement $user): void
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        if ($generalConfig->rememberUsernameDuration !== 0) {
            $cookie = new Cookie($this->usernameCookie);
            $cookie->value = $user->username;
            $seconds = ConfigHelper::durationInSeconds($generalConfig->rememberUsernameDuration);
            $cookie->expire = time() + $seconds;
            Craft::$app->getResponse()->getCookies()->add($cookie);
        } else {
            Craft::$app->getResponse()->getCookies()->remove(new Cookie($this->usernameCookie));
        }
    }

    /**
     * @inheritdoc
     */
    public function getReturnUrl($defaultUrl = null): string
    {
        // Set the default based on the config, if it’s not specified
        if ($defaultUrl === null) {
            // Is this a control panel request and can they access the control panel?
            if (Craft::$app->getRequest()->getIsCpRequest() && $this->checkPermission('accessCp')) {
                $defaultUrl = UrlHelper::cpUrl(Craft::$app->getConfig()->getGeneral()->getPostCpLoginRedirect());
            } else {
                $defaultUrl = UrlHelper::siteUrl(Craft::$app->getConfig()->getGeneral()->getPostLoginRedirect());
            }
        }

        $url = parent::getReturnUrl($defaultUrl);

        // Strip out any tags that may have gotten in there by accident
        // i.e. if there was a {siteUrl} tag in the Site URL setting, but no matching environment variable,
        // so they ended up on something like http://example.com/%7BsiteUrl%7D/some/path
        return str_replace(['{', '}'], '', $url);
    }

    /**
     * Removes the stored return URL, if there is one.
     *
     * @see getReturnUrl()
     */
    public function removeReturnUrl(): void
    {
        SessionHelper::remove($this->returnUrlParam);
    }

    /**
     * Returns the user token from the session.
     *
     * @return string|null
     * @since 3.6.11
     */
    public function getToken(): ?string
    {
        return SessionHelper::get($this->tokenParam);
    }

    /**
     * Returns the username of the account that the browser was last logged in as.
     *
     * ---
     *
     * ```php
     * $username = Craft::$app->user->rememberedUsername;
     * ```
     * ```twig{5}
     * <form method="post" action="" accept-charset="UTF-8">
     *   {{ csrfInput() }}
     *   {{ actionInput('users/login') }}
     *
     *   {% set username = craft.app.user.rememberedUsername %}
     *   <input type="text" name="loginName" value="{{ username }}">
     *
     *   <input type="password" name="password">
     *
     *   <input type="submit" value="Sign in">
     * </form>
     * ```
     *
     * @return string|null
     */
    public function getRememberedUsername(): ?string
    {
        return Craft::$app->getRequest()->getCookies()->getValue($this->usernameCookie['name']);
    }

    /**
     * @inheritdoc
     *
     * ---
     *
     * ```php{1}
     * $isGuest = Craft::$app->user->isGuest;
     * ```
     * ```twig
     * {% if craft.app.user.isGuest %}
     *   <a href="{{ url(craft.app.config.general.getLoginPath()) }}">
     *     Login
     *   </a>
     * {% else %}
     *   <a href="{{ url(craft.app.config.general.getLogoutPath()) }}">
     *     Logout
     *   </a>
     * {% endif %}
     * ```
     */
    public function getIsGuest(): bool
    {
        return parent::getIsGuest();
    }

    /**
     * Redirects the user browser away from a guest page.
     *
     * @return Response the redirection response
     * @throws ForbiddenHttpException if the request doesn’t accept a redirect response
     * @since 3.4.0
     */
    public function guestRequired(): Response
    {
        if (!$this->checkRedirectAcceptable()) {
            throw new ForbiddenHttpException(Craft::t('app', 'Guest Required'));
        }
        return Craft::$app->getResponse()->redirect($this->getReturnUrl());
    }

    /**
     * Returns how many seconds are left in the current user session.
     *
     * @return int The seconds left in the session, or -1 if their session will expire when their HTTP session ends.
     */
    public function getRemainingSessionTime(): int
    {
        // Are they logged in?
        if (!$this->getIsGuest()) {
            if (!isset($this->authTimeout)) {
                // The session duration must have been empty (expire when the HTTP session ends)
                return -1;
            }

            $expire = SessionHelper::get($this->authTimeoutParam);
            $time = time();

            if ($expire !== null && $expire > $time) {
                return $expire - $time;
            }
        }

        return 0;
    }

    // Authorization
    // -------------------------------------------------------------------------

    /**
     * Returns whether the current user is an admin.
     *
     * @return bool Whether the current user is an admin.
     */
    public function getIsAdmin(): bool
    {
        $user = $this->getIdentity();

        return ($user && $user->admin);
    }

    /**
     * Returns whether the current user has a given permission.
     *
     * @param string $permissionName The name of the permission.
     * @return bool Whether the current user has the permission.
     */
    public function checkPermission(string $permissionName): bool
    {
        $user = $this->getIdentity();

        return ($user && $user->can($permissionName));
    }

    /**
     * Returns how many seconds are left in the current elevated user session.
     *
     * @return int|false The number of seconds left in the current elevated user session
     * or false if it has been disabled.
     */
    public function getElevatedSessionTimeout(): int|false
    {
        // Are they logged in?
        if (!$this->getIsGuest()) {
            $expires = SessionHelper::get($this->elevatedSessionTimeoutParam);

            if ($expires !== null) {
                $currentTime = time();

                if ($expires > $currentTime) {
                    return $expires - $currentTime;
                }
            }
        }

        // If it has been disabled, return false.
        if (Craft::$app->getConfig()->getGeneral()->elevatedSessionDuration === 0) {
            return false;
        }

        return 0;
    }

    /**
     * Returns whether the user currently has an elevated session.
     *
     * @return bool Whether the user currently has an elevated session
     */
    public function getHasElevatedSession(): bool
    {
        // If it's been disabled, just return true
        if (Craft::$app->getConfig()->getGeneral()->elevatedSessionDuration === 0) {
            return true;
        }

        return ($this->getElevatedSessionTimeout() !== 0);
    }

    /**
     * Starts an elevated user session for the current user.
     *
     * @param string $password the current user’s password
     * @return bool Whether the password was valid, and the user session has been elevated
     * @throws UserLockedException if the user is locked.
     */
    public function startElevatedSession(string $password): bool
    {
        // If the current user is being impersonated by an admin, get the admin instead
        if ($previousUserId = SessionHelper::get(UserElement::IMPERSONATE_KEY)) {
            /** @var UserElement $user */
            $user = UserElement::find()
                ->addSelect(['users.password'])
                ->id($previousUserId)
                ->one();
        } else {
            // Get the current user
            $user = $this->getIdentity();
        }

        if (!$user || $user->password === null) {
            // Delay again to match $user->authenticate()'s delay
            Craft::$app->getSecurity()->validatePassword('p@ss1w0rd', '$2y$13$nj9aiBeb7RfEfYP3Cum6Revyu14QelGGxwcnFUKXIrQUitSodEPRi');
            $this->_handleLoginFailure(UserElement::AUTH_INVALID_CREDENTIALS);
            return false;
        }

        if ($user->locked) {
            throw new UserLockedException($user);
        }

        // Validate the password
        $validator = new UserPasswordValidator();

        // Did they submit a valid password, and is the user capable of being logged-in?
        if (!$validator->validate($password) || !$user->authenticate($password)) {
            $this->_handleLoginFailure($user->authError, $user);
            return false;
        }

        // Make sure elevated sessions haven't been disabled
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        if ($generalConfig->elevatedSessionDuration === 0) {
            return true;
        }

        // Set the elevated session expiration date
        $timeout = time() + $generalConfig->elevatedSessionDuration;
        SessionHelper::set($this->elevatedSessionTimeoutParam, $timeout);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function login(IdentityInterface $identity, $duration = 0): bool
    {
        $authTimeout = $this->authTimeout;
        if ($duration > 0) {
            // Set authTimeout to the duration so it gets factored into the session's expiration time in switchIdentity()
            $this->authTimeout = $duration;
        }
        $success = parent::login($identity, $duration);
        $this->authTimeout = $authTimeout;
        return $success;
    }

    /**
     * @inheritdoc
     */
    protected function beforeLogin($identity, $cookieBased, $duration): bool
    {
        // Only allow the login if the request meets our user agent and IP requirements
        if (!$this->_validateUserAgentAndIp()) {
            return false;
        }

        return parent::beforeLogin($identity, $cookieBased, $duration);
    }

    /**
     * @inheritdoc
     */
    protected function afterLogin($identity, $cookieBased, $duration): void
    {
        /** @var UserElement $identity */

        if ($duration > 0) {
            // Store the duration on the session
            SessionHelper::set($this->authDurationParam, $duration);
        } else {
            SessionHelper::remove($this->authDurationParam);
        }

        // Save the username cookie if they're not being impersonated
        $impersonating = SessionHelper::get(UserElement::IMPERSONATE_KEY) !== null;
        if (!$impersonating) {
            $this->sendUsernameCookie($identity);
        }

        // Clear out the elevated session, if there is one
        SessionHelper::remove($this->elevatedSessionTimeoutParam);

        // Update the user record
        if (!$impersonating) {
            Craft::$app->getUsers()->handleValidLogin($identity);
        }

        parent::afterLogin($identity, $cookieBased, $duration);
    }

    /**
     * @inheritdoc
     */
    public function switchIdentity($identity, $duration = 0): void
    {
        if ($this->enableSession) {
            SessionHelper::remove($this->tokenParam);

            if ($identity) {
                /** @var UserElement $identity */
                // Generate a new session token
                $this->generateToken($identity->id);
            }
        }

        parent::switchIdentity($identity, $duration);
    }

    /**
     * Generates a new user session token.
     *
     * @param int $userId
     * @since 3.1.1
     */
    public function generateToken(int $userId): void
    {
        $token = Craft::$app->getSecurity()->generateRandomString(100);

        Db::insert(Table::SESSIONS, [
            'userId' => $userId,
            'token' => $token,
        ]);

        SessionHelper::set($this->tokenParam, $token);
    }

    /**
     * @inheritdoc
     */
    protected function renewAuthStatus(): void
    {
        // Only renew if the request meets our user agent and IP requirements
        if (!Craft::$app->getIsInstalled() || !$this->_validateUserAgentAndIp()) {
            return;
        }

        // Should we be extending the user’s session on this request?
        $extendSession = !Craft::$app->getRequest()->getParam('dontExtendSession');

        // Prevent the user session from getting extended?
        if (isset($this->authTimeout) && !$extendSession) {
            $this->absoluteAuthTimeout = $this->authTimeout;
            $this->authTimeout = null;
            $absoluteAuthTimeoutParam = $this->absoluteAuthTimeoutParam;
            $this->absoluteAuthTimeoutParam = $this->authTimeoutParam;
            $autoRenewCookie = $this->autoRenewCookie;
            $this->autoRenewCookie = false;
            parent::renewAuthStatus();
            $this->authTimeout = $this->absoluteAuthTimeout;
            $this->absoluteAuthTimeout = null;
            $this->absoluteAuthTimeoutParam = $absoluteAuthTimeoutParam;
            $this->autoRenewCookie = $autoRenewCookie;
        } else {
            $authTimeout = $this->authTimeout;
            // Was a specific session duration specified on login?
            if (SessionHelper::has($this->authDurationParam)) {
                $this->authTimeout = SessionHelper::get($this->authDurationParam);
            }
            parent::renewAuthStatus();
            $this->authTimeout = $authTimeout;
        }
    }

    /**
     * @inheritdoc
     */
    protected function beforeLogout($identity): bool
    {
        /** @var UserElement $identity */
        if (!parent::beforeLogout($identity)) {
            return false;
        }

        // Stop keeping track of the session duration specified on login
        SessionHelper::remove($this->authDurationParam);

        // Delete the session token in the database
        $token = $this->getToken();
        if ($token !== null) {
            SessionHelper::remove($this->tokenParam);
            Db::delete(Table::SESSIONS, [
                'token' => $token,
                'userId' => $identity->id,
            ]);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function afterLogout($identity): void
    {
        /** @var UserElement $identity */
        // Delete the impersonation session, if there is one
        SessionHelper::remove(UserElement::IMPERSONATE_KEY);

        if (Craft::$app->getConfig()->getGeneral()->enableCsrfProtection) {
            // Let's keep the current nonce around.
            Craft::$app->getRequest()->regenCsrfToken();
        }

        parent::afterLogout($identity);
    }

    /**
     * Validates that the request has a user agent and IP associated with it,
     * if the 'requireUserAgentAndIpForSession' config setting is enabled.
     *
     * @return bool
     */
    private function _validateUserAgentAndIp(): bool
    {
        if (!Craft::$app->getConfig()->getGeneral()->requireUserAgentAndIpForSession) {
            return true;
        }

        $request = Craft::$app->getRequest();

        if ($request->getUserAgent() === null || $request->getUserIP() === null) {
            Craft::warning('Request didn’t meet the user agent and IP requirement for maintaining a user session.', __METHOD__);
            return false;
        }

        return true;
    }

    /**
     * @param string|null $authError
     * @param UserElement|null $user
     */
    private function _handleLoginFailure(?string $authError, ?UserElement $user = null): void
    {
        $message = UserHelper::getLoginFailureMessage($authError, $user);

        // Fire a 'loginFailure' event
        $event = new LoginFailureEvent([
            'authError' => $authError,
            'message' => $message,
            'user' => $user,
        ]);
        $this->trigger(UsersController::EVENT_LOGIN_FAILURE, $event);
    }
}
