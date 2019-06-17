<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\controllers\UsersController;
use craft\db\Query;
use craft\db\Table;
use craft\elements\User as UserElement;
use craft\errors\UserLockedException;
use craft\events\LoginFailureEvent;
use craft\helpers\ConfigHelper;
use craft\helpers\Db;
use craft\helpers\UrlHelper;
use craft\helpers\User as UserHelper;
use craft\validators\UserPasswordValidator;
use yii\web\Cookie;

/**
 * The User component provides APIs for managing the user authentication status.
 *
 * An instance of the User component is globally accessible in Craft via [[\craft\base\ApplicationTrait::getUser()|`Craft::$app->user`]].
 *
 * @property bool $hasElevatedSession Whether the user currently has an elevated session
 * @property UserElement|null $identity The logged-in user.
 * @method UserElement|null getIdentity($autoRenew = true) Returns the logged-in user.
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class User extends \yii\web\User
{
    // Properties
    // =========================================================================

    /**
     * @var string the session variable name used to store the user session token.
     */
    public $tokenParam = '__token';

    /**
     * @var array The configuration of the username cookie.
     * @see Cookie
     */
    public $usernameCookie;

    /**
     * @var string The session variable name used to store the value of the expiration timestamp of the elevated session state.
     */
    public $elevatedSessionTimeoutParam = '__elevated_timeout';

    // Public Methods
    // =========================================================================

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
     * This method is used after a user is logged in. It saves the logged-in user's username in a cookie,
     * so that login forms can remember the initial Username value on login forms.
     *
     * @param UserElement $user
     * @see afterLogin()
     */
    public function sendUsernameCookie(UserElement $user)
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
    public function getReturnUrl($defaultUrl = null)
    {
        // Set the default based on the config, if it's not specified
        if ($defaultUrl === null) {
            // Is this a CP request and can they access the CP?
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
        $url = str_replace(['{', '}'], '', $url);

        return $url;
    }

    /**
     * Removes the stored return URL, if there is one.
     *
     * @see getReturnUrl()
     */
    public function removeReturnUrl()
    {
        Craft::$app->getSession()->remove($this->returnUrlParam);
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
     *     {{ csrfInput() }}
     *     <input type="hidden" name="action" value="users/login">
     *
     *     {% set username = craft.app.user.rememberedUsername %}
     *     <input type="text" name="loginName" value="{{ username }}">
     *
     *     <input type="password" name="password">
     *
     *     <input type="submit" value="Login">
     * </form>
     * ```
     *
     * @return string|null
     */
    public function getRememberedUsername()
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
     *     <a href="{{ url(craft.app.config.general.getLoginPath()) }}">
     *         Login
     *     </a>
     * {% else %}
     *     <a href="{{ url(craft.app.config.general.getLogoutPath()) }}">
     *         Logout
     *     </a>
     * {% endif %}
     * ```
     */
    public function getIsGuest()
    {
        return parent::getIsGuest();
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
            if ($this->authTimeout === null) {
                // The session duration must have been empty (expire when the HTTP session ends)
                return -1;
            }

            $expire = Craft::$app->getSession()->get($this->authTimeoutParam);
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
     * @return int|bool The number of seconds left in the current elevated user session
     * or false if it has been disabled.
     */
    public function getElevatedSessionTimeout()
    {
        // Are they logged in?
        if (!$this->getIsGuest()) {
            $session = Craft::$app->getSession();
            $expires = $session->get($this->elevatedSessionTimeoutParam);

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
        $session = Craft::$app->getSession();

        // If the current user is being impersonated by an admin, get the admin instead
        if ($previousUserId = $session->get(UserElement::IMPERSONATE_KEY)) {
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
        $session->set($this->elevatedSessionTimeoutParam, $timeout);

        return true;
    }

    // Misc
    // -------------------------------------------------------------------------

    /**
     * Saves the logged-in user’s Debug toolbar preferences to the session.
     */
    public function saveDebugPreferencesToSession()
    {
        $identity = $this->getIdentity();
        $session = Craft::$app->getSession();

        $this->destroyDebugPreferencesInSession();

        if ($identity->admin && $identity->getPreference('enableDebugToolbarForSite')) {
            $session->set('enableDebugToolbarForSite', true);
        }

        if ($identity->admin && $identity->getPreference('enableDebugToolbarForCp')) {
            $session->set('enableDebugToolbarForCp', true);
        }
    }

    /**
     * Removes the debug preferences from the session.
     */
    public function destroyDebugPreferencesInSession()
    {
        $session = Craft::$app->getSession();
        $session->remove('enableDebugToolbarForSite');
        $session->remove('enableDebugToolbarForCp');
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function beforeLogin($identity, $cookieBased, $duration)
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
    protected function afterLogin($identity, $cookieBased, $duration)
    {
        /** @var UserElement $identity */
        $session = Craft::$app->getSession();

        // Save the username cookie if they're not being impersonated
        $impersonating = $session->get(UserElement::IMPERSONATE_KEY) !== null;
        if (!$impersonating) {
            $this->sendUsernameCookie($identity);
        }

        // Save the Debug preferences to the session
        $this->saveDebugPreferencesToSession();

        // Clear out the elevated session, if there is one
        $session->remove($this->elevatedSessionTimeoutParam);

        // Update the user record
        if (!$impersonating) {
            Craft::$app->getUsers()->handleValidLogin($identity);
        }

        parent::afterLogin($identity, $cookieBased, $duration);
    }

    /**
     * @inheritdoc
     */
    public function switchIdentity($identity, $duration = 0)
    {
        if ($this->enableSession) {
            $session = Craft::$app->getSession();
            $session->remove($this->tokenParam);

            if ($identity) {
                /** @var UserElement $identity */
                // Generate a new session token
                $this->generateToken($identity->id);
            }
        }

        return parent::switchIdentity($identity, $duration);
    }

    /**
     * Generates a new user session token.
     *
     * @param int $userId
     */
    public function generateToken(int $userId)
    {
        $token = Craft::$app->getSecurity()->generateRandomString(100);

        Craft::$app->getDb()->createCommand()
            ->insert(Table::SESSIONS, [
                'userId' => $userId,
                'token' => $token,
            ])
            ->execute();

        Craft::$app->getSession()->set($this->tokenParam, $token);
    }

    /**
     * @inheritdoc
     */
    protected function renewAuthStatus()
    {
        // Only renew if the request meets our user agent and IP requirements
        if (!Craft::$app->getIsInstalled() || !$this->_validateUserAgentAndIp()) {
            return;
        }

        // Should we be extending the user's session on this request?
        $extendSession = !Craft::$app->getRequest()->getParam('dontExtendSession');

        // Make sure their user session token is valid
        $this->_validateToken($extendSession);

        // Prevent the user session from getting extended?
        if ($this->authTimeout !== null && !$extendSession) {
            $this->absoluteAuthTimeout = $this->authTimeout;
            $this->authTimeout = null;
            $absoluteAuthTimeoutParam = $this->absoluteAuthTimeoutParam;
            $this->absoluteAuthTimeoutParam = $this->authTimeoutParam;
            parent::renewAuthStatus();
            $this->authTimeout = $this->absoluteAuthTimeout;
            $this->absoluteAuthTimeout = null;
            $this->absoluteAuthTimeoutParam = $absoluteAuthTimeoutParam;
        } else {
            parent::renewAuthStatus();
        }
    }

    /**
     * @inheritdoc
     */
    protected function afterLogout($identity)
    {
        /** @var UserElement $identity */
        // Delete the impersonation session, if there is one
        $session = Craft::$app->getSession();
        $session->remove(UserElement::IMPERSONATE_KEY);

        // Delete the session token
        $token = $session->get($this->tokenParam);
        if ($token !== null) {
            $session->remove($this->tokenParam);
            Craft::$app->getDb()->createCommand()
                ->delete(Table::SESSIONS, [
                    'token' => $token,
                    'userId' => $identity->id,
                ])
                ->execute();
        }

        $this->destroyDebugPreferencesInSession();

        if (Craft::$app->getConfig()->getGeneral()->enableCsrfProtection) {
            // Let's keep the current nonce around.
            Craft::$app->getRequest()->regenCsrfToken();
        }

        parent::afterLogout($identity);
    }

    // Private Methods
    // =========================================================================

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
     * Validates that a user's session token is valid.
     *
     * @param bool $extendSession
     */
    private function _validateToken(bool $extendSession)
    {
        $session = Craft::$app->getSession();
        $id = $session->getHasSessionId() || $session->getIsActive() ? $session->get($this->idParam) : null;

        if ($id === null) {
            return;
        }

        $token = $session->get($this->tokenParam);

        if ($token === null) {
            // Just give them a new token and be done with it
            $this->generateToken($id);
            return;
        }

        $tokenId = (new Query())
            ->select(['id'])
            ->from([Table::SESSIONS])
            ->where([
                'token' => $token,
                'userId' => $id,
            ])
            ->scalar();

        if (!$tokenId) {
            // Kill their PHP session. Their session may still be auto-renewed via their session cookie, though
            $session->remove($this->idParam);
            $session->remove($this->tokenParam);
            return;
        }

        if ($extendSession) {
            // Update the session row's dateUpdated value so it doesn't get GC'd
            Craft::$app->getDb()->createCommand()
                ->update(Table::SESSIONS, [
                    'dateUpdated' => Db::prepareDateForDb(new \DateTime()),
                ], ['id' => $tokenId])
                ->execute();
        }
    }

    /**
     * @param string $authError
     * @param UserElement $user
     * @return null
     */
    private function _handleLoginFailure(string $authError = null, UserElement $user = null)
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
