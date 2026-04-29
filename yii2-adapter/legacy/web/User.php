<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Carbon\CarbonInterval;
use Craft;
use CraftCms\Cms\Auth\Concerns\ConfirmsPasswords;
use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Auth\Passkeys\Passkeys;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\User\Elements\User as UserElement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use yii\web\ForbiddenHttpException;
use yii\web\IdentityInterface;
use function CraftCms\Cms\t;

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
class User extends \CraftCms\Yii2Adapter\Web\User
{
    use ConfirmsPasswords;

    /**
     * @var string The session variable name used to store the duration of the authenticated state.
     * @since 3.6.8
     */
    public string $authDurationParam = '__duration';

    /**
     * @var string the session variable name used to store the user session token.
     */
    public string $tokenParam = '__token';

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
        return Auth::loginUsingId($userId, $duration > 0) !== false;
    }

    /**
     * Sends a username cookie.
     *
     * This method is used after a user is logged in. It saves the logged-in user’s username in a cookie,
     * so that login forms can remember the initial Username value on login forms.
     *
     * @param UserElement $user
     *
     * @see afterLogin()
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Auth\AuthMethods::setRememberedUsername()} instead.
     */
    public function sendUsernameCookie(UserElement $user): void
    {
        app(\CraftCms\Cms\Auth\AuthMethods::class)->setRememberedUsername($user);
    }

    /**
     * @inheritdoc
     * @deprecated 6.0.0 use {@see URL::returnUrl()} instead.
     */
    public function getReturnUrl($defaultUrl = null): string
    {
        return URL::returnUrl($defaultUrl);
    }

    /**
     * Returns the default return URL.
     *
     * @return string
     * @since 5.6.2
     * @deprecated 6.0.0 use {@see URL::defaultReturnUrl()} instead.
     */
    public function getDefaultReturnUrl(): string
    {
        return URL::defaultReturnUrl();
    }

    /**
     * Removes the stored return URL, if there is one.
     *
     * @see getReturnUrl()
     * @deprecated 6.0.0 use {@see \Illuminate\Support\Facades\Session::forget('_previous.url')} instead.
     */
    public function removeReturnUrl(): void
    {
        \Illuminate\Support\Facades\Session::forget('_previous.url');
    }

    /**
     * Returns the user token from the session.
     *
     * @return string|null
     * @since 3.6.11
     */
    public function getToken(): ?string
    {
        return \Illuminate\Support\Facades\Session::get($this->tokenParam);
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
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Auth\AuthMethods::getRememberedUsername()} instead.
     */
    public function getRememberedUsername(): ?string
    {
        return app(\CraftCms\Cms\Auth\AuthMethods::class)->getRememberedUsername();
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
     *
     * @deprecated 6.0.0 use {@see \Illuminate\Support\Facades\Auth::guest()} instead.
     */
    public function getIsGuest(): bool
    {
        return Auth::guest();
    }

    /**
     * Redirects the user browser away from a guest page.
     *
     * @return Response the redirection response
     * @throws ForbiddenHttpException if the request doesn’t accept a redirect response
     * @since 3.4.0
     * @deprecated 6.0.0 use the "guest" middleware instead.
     */
    public function guestRequired(): Response
    {
        if (!$this->checkRedirectAcceptable()) {
            throw new ForbiddenHttpException(t('Guest Required'));
        }
        return Craft::$app->getResponse()->redirect(URL::returnUrl());
    }

    /**
     * Returns how many seconds are left in the current user session.
     *
     * @return int The seconds left in the session, or -1 if their session will expire when their HTTP session ends.
     */
    public function getRemainingSessionTime(): int
    {
        // Are they logged in?
        if (Auth::check()) {
            return (int) CarbonInterval::minutes(config('session.lifetime', 120))->totalSeconds;
        }

        return 0;
    }

    /**
     * Returns the original user, if the current user is being impersonated.
     *
     * @return UserElement|null
     * @since 5.6.0
     * @deprecated 6.0.0 use {@see Impersonation::getImpersonator()} instead.
     */
    public function getImpersonator(): ?UserElement
    {
        return app(Impersonation::class)->getImpersonator();
    }

    /**
     * Returns the ID of the original user, if the current user is being impersonated.
     *
     * @return int|null
     * @since 5.6.0
     * @deprecated 6.0.0 use {@see Impersonation::getImpersonatorId()} instead.
     */
    public function getImpersonatorId(): ?int
    {
        return app(Impersonation::class)->getImpersonatorId();
    }

    /**
     * Sets the ID of the original user, if the current user is being impersonated.
     *
     * @param int|null $id
     *
     * @since 5.6.0
     * @deprecated 6.0.0 use {@see Impersonation::setImpersonatorId()} instead.
     */
    public function setImpersonatorId(?int $id): void
    {
        app(Impersonation::class)->setImpersonatorId($id);
    }

    // Authorization
    // -------------------------------------------------------------------------

    /**
     * Returns whether the current user is an admin.
     *
     * @return bool Whether the current user is an admin.
     * @deprecated 6.0.0 use `Auth::user()?->isAdmin()` instead.
     */
    public function getIsAdmin(): bool
    {
        $user = Auth::user();

        return ($user && $user->admin);
    }

    /**
     * Returns whether the current user has a given permission.
     *
     * @param string $permissionName The name of the permission.
     * @return bool Whether the current user has the permission.
     * @deprecated 6.0.0 use {@see Gate::check} instead.
     */
    public function checkPermission(string $permissionName): bool
    {
        return Gate::check($permissionName);
    }

    /**
     * Returns how many seconds are left in the current elevated user session.
     *
     * @return int|false The number of seconds left in the current elevated user session
     * or false if it has been disabled.
     * @deprecated 6.0.0 use {@see ConfirmsPasswords::confirmedPasswordTimeout()} instead.
     */
    public function getElevatedSessionTimeout(): int|false
    {
        return $this->confirmedPasswordTimeout();
    }

    /**
     * Returns whether the user currently has an elevated session.
     *
     * @return bool Whether the user currently has an elevated session
     * @deprecated 6.0.0 use {@see ConfirmsPasswords::isPasswordConfirmed()} instead.
     */
    public function getHasElevatedSession(): bool
    {
        return $this->isPasswordConfirmed();
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

        if ($success) {
            // Set the elevated session expiration date
            $generalConfig = Cms::config();
            if ($generalConfig->elevatedSessionDuration !== 0) {
                \Illuminate\Support\Facades\Session::passwordConfirmed();
            }
        }

        $this->authTimeout = $authTimeout;
        return $success;
    }

    /**
     * @inheritdoc
     */
    protected function afterLogin($identity, $cookieBased, $duration): void
    {
        if ($duration > 0) {
            // Store the duration on the session
            \Illuminate\Support\Facades\Session::put($this->authDurationParam, $duration);
        } else {
            \Illuminate\Support\Facades\Session::forget($this->authDurationParam);
        }

        $this->_clearOtherSessionParams();

        // Save the username cookie if they're not being impersonated
        $impersonator = app(Impersonation::class)->getImpersonator();
        if (!$impersonator) {
            app(\CraftCms\Cms\Auth\AuthMethods::class)->setRememberedUsername(UserElement::find()->id($identity->getId())->firstOrFail());
        }

        // Update the user record
        if (!$impersonator) {
            Users::handleValidLogin(UserElement::find()->id($identity->getId())->firstOrFail());
        }

        parent::afterLogin($identity, $cookieBased, $duration);
    }

    /**
     * @inheritdoc
     */
    public function setReturnUrl($url): void
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if ($scheme && !in_array($scheme, ['http', 'https'])) {
            $url = '/';
        }
        parent::setReturnUrl(strip_tags($url));
    }

    /**
     * @inheritdoc
     */
    protected function renewAuthStatus(): void
    {
        // Only renew if the request meets our user agent and IP requirements
        if (!Cms::isInstalled()) {
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
            if (\Illuminate\Support\Facades\Session::has($this->authDurationParam)) {
                $this->authTimeout = \Illuminate\Support\Facades\Session::get($this->authDurationParam);
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
        if (!parent::beforeLogout($identity)) {
            return false;
        }

        // Stop keeping track of the session duration specified on login
        \Illuminate\Support\Facades\Session::forget($this->authDurationParam);

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function afterLogout($identity): void
    {
        $this->_clearOtherSessionParams();

        if (Cms::config()->enableCsrfProtection) {
            // Let's keep the current nonce around.
            Craft::$app->getRequest()->getCsrfToken(true);
        }

        parent::afterLogout($identity);
    }

    private function _clearOtherSessionParams(): void
    {
        // Make sure 2FA data doesn't bleed over
        app(\CraftCms\Cms\Auth\AuthMethods::class)->setUser(null);
        \Illuminate\Support\Facades\Session::forget(app(Passkeys::class)->passkeyCreationOptionsParam);
    }
}
